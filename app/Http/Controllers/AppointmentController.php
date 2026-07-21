<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Patient;
use App\Models\Report;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    private const STATUSES = ['booked', 'completed', 'cancelled', 'no_show'];

    private function formData(): array
    {
        return [
            'patients' => Patient::orderBy('name')->get(),
            'doctors' => Employee::orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
            'statuses' => self::STATUSES,
        ];
    }

    public function index()
    {
        $appointments = Appointment::with(['patient', 'doctor', 'branch'])
            ->orderByDesc('scheduled_at')->get();

        return view('appointments.index', compact('appointments'));
    }

    public function create()
    {
        return view('appointments.form', array_merge($this->formData(), [
            'appointment' => new Appointment(), 'report' => null,
            'action' => route('appointments.store'), 'method' => 'POST',
        ]));
    }

    public function store(Request $request)
    {
        if (! $request->input('scheduled_at')) {
            return back()->withInput()->with('flash', ['type' => 'error', 'message' => 'Please choose a valid date and time.']);
        }
        $appointment = Appointment::create($this->data($request));
        $this->syncNotes($appointment, $request);

        return redirect()->route('appointments.index')->with('flash', ['type' => 'success', 'message' => 'Appointment booked.']);
    }

    public function edit(Appointment $appointment)
    {
        return view('appointments.form', array_merge($this->formData(), [
            'appointment' => $appointment,
            'report' => Report::where('appointment_id', $appointment->id)->first(),
            'action' => route('appointments.update', $appointment), 'method' => 'PUT',
        ]));
    }

    public function update(Request $request, Appointment $appointment)
    {
        $appointment->update($this->data($request));
        $this->syncNotes($appointment, $request);

        return redirect()->route('appointments.index')->with('flash', ['type' => 'success', 'message' => 'Appointment updated.']);
    }

    /**
     * The doctor's clinical notes now live on the appointment form, but are still
     * stored in the `report` table (one note record per visit) so the database
     * design is unchanged. Creates, updates or clears the note as needed.
     */
    private function syncNotes(Appointment $appointment, Request $request): void
    {
        $fields = [
            'diagnosis' => $request->input('diagnosis') ?: null,
            'notes' => $request->input('notes') ?: null,
            'next_visit' => $request->input('next_visit') ?: null,
        ];
        $report = Report::where('appointment_id', $appointment->id)->first();

        // Nothing filled in — remove any existing note for this visit.
        if (! array_filter($fields)) {
            $report?->delete();

            return;
        }

        $fields += [
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'doctor_id' => $appointment->doctor_id,
        ];
        $report ? $report->update($fields) : Report::create($fields);
    }

    public function destroy(Appointment $appointment)
    {
        $appointment->delete();

        return redirect()->route('appointments.index')->with('flash', ['type' => 'success', 'message' => 'Appointment deleted.']);
    }

    private function data(Request $request): array
    {
        return [
            'patient_id' => $request->input('patient_id'),
            'doctor_id' => $request->input('doctor_id'),
            'branch_id' => $request->input('branch_id'),
            'scheduled_at' => $request->input('scheduled_at'),
            'status' => $request->input('status') ?: 'booked',
        ];
    }
}
