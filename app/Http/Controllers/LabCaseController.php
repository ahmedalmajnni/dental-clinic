<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\LabCase;
use App\Models\Report;
use App\Services\Billing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LabCaseController extends Controller
{
    private const STATUSES = ['delivered', 'cancelled'];
    private const TYPES = ['Crown', 'Bridge', 'Denture', 'Veneer', 'Implant', 'Retainer', 'Night guard'];

    private function formData(): array
    {
        return [
            'appointments' => Appointment::with(['patient', 'doctor'])
                ->where('status', 'completed')
                ->whereHas('report', fn ($query) => $query->whereNotNull('next_visit'))
                ->orderByDesc('scheduled_at')
                ->get(),
            'statuses' => self::STATUSES,
            'commonTypes' => self::TYPES,
        ];
    }

    public function index()
    {
        $labCases = LabCase::with(['patient', 'doctor'])
            ->orderByRaw("(status = 'delivered' OR status = 'cancelled')")
            ->orderByRaw('due_date NULLS LAST')
            ->orderByDesc('created_at')->get();

        return view('lab_cases.index', compact('labCases'));
    }

    public function create()
    {
        return view('lab_cases.form', array_merge($this->formData(), [
            'labCase' => new LabCase(), 'action' => route('lab-cases.store'), 'method' => 'POST',
        ]));
    }

    public function store(Request $request)
    {
        $appointment = $this->completedAppointment($request);
        if (! $appointment) {
            return back()->withInput()->with('flash', ['type' => 'error', 'message' => 'Please choose a completed visit with a next visit.']);
        }

        $labCase = LabCase::create($this->data($request, $appointment));
        Billing::addClinicalCharge($labCase->patient_id, 'lab_case_id', $labCase->id, 'Lab case: '.$labCase->type, (float) $labCase->cost);

        return redirect()->route('lab-cases.index')->with('flash', ['type' => 'success', 'message' => 'Lab case created.']);
    }

    public function edit(LabCase $labCase)
    {
        return view('lab_cases.form', array_merge($this->formData(), [
            'labCase' => $labCase, 'action' => route('lab-cases.update', $labCase), 'method' => 'PUT',
        ]));
    }

    public function update(Request $request, LabCase $labCase)
    {
        $appointment = $this->completedAppointment($request);
        if (! $appointment) {
            return back()->withInput()->with('flash', ['type' => 'error', 'message' => 'Please choose a completed visit with a next visit.']);
        }

        DB::transaction(function () use ($request, $appointment, $labCase) {
            Billing::removeClinicalCharge('lab_case_id', $labCase->id);
            $labCase->update($this->data($request, $appointment));
            Billing::addClinicalCharge($labCase->patient_id, 'lab_case_id', $labCase->id, 'Lab case: '.$labCase->type, (float) $labCase->cost);
        });

        return redirect()->route('lab-cases.index')->with('flash', ['type' => 'success', 'message' => 'Lab case updated.']);
    }

    public function destroy(LabCase $labCase)
    {
        Billing::removeClinicalCharge('lab_case_id', $labCase->id);
        $labCase->delete();

        return redirect()->route('lab-cases.index')->with('flash', ['type' => 'success', 'message' => 'Lab case deleted.']);
    }

    private function completedAppointment(Request $request): ?Appointment
    {
        return Appointment::with(['patient', 'doctor'])
            ->whereKey($request->input('appointment_id'))
            ->where('status', 'completed')
            ->whereHas('report', fn ($query) => $query->whereNotNull('next_visit'))
            ->first();
    }

    private function data(Request $request, Appointment $appointment): array
    {
        return [
            'patient_id' => $appointment->patient_id,
            'doctor_id' => $appointment->doctor_id,
            'type' => $request->input('type'),
            'due_date' => $request->input('due_date')
                ?: $appointment->report->next_visit->copy()->subDay()->toDateString(),
            'status' => in_array($request->input('status'), self::STATUSES, true)
                ? $request->input('status')
                : 'delivered',
            'cost' => (float) ($request->input('cost') ?: 0),
        ];
    }
}
