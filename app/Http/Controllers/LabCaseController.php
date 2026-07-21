<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LabCase;
use App\Models\Patient;
use Illuminate\Http\Request;

class LabCaseController extends Controller
{
    private const STATUSES = ['received', 'in_progress', 'ready', 'delivered', 'cancelled'];
    private const TYPES = ['Crown', 'Bridge', 'Denture', 'Veneer', 'Implant', 'Retainer', 'Night guard'];

    private function formData(): array
    {
        return [
            'patients' => Patient::orderBy('name')->get(),
            'doctors' => Employee::orderBy('name')->get(),
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
        LabCase::create($this->data($request));

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
        $labCase->update($this->data($request));

        return redirect()->route('lab-cases.index')->with('flash', ['type' => 'success', 'message' => 'Lab case updated.']);
    }

    public function destroy(LabCase $labCase)
    {
        $labCase->delete();

        return redirect()->route('lab-cases.index')->with('flash', ['type' => 'success', 'message' => 'Lab case deleted.']);
    }

    private function data(Request $request): array
    {
        return [
            'patient_id' => $request->input('patient_id'),
            'doctor_id' => $request->input('doctor_id'),
            'type' => $request->input('type'),
            'due_date' => $request->input('due_date') ?: null,
            'status' => $request->input('status') ?: 'received',
            'cost' => (float) ($request->input('cost') ?: 0),
        ];
    }
}
