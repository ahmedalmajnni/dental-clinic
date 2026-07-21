<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatientController extends Controller
{
    public function index()
    {
        return view('patients.index', [
            'patients' => Patient::orderBy('name')->get(),          // active only (soft-deletes excluded)
            'archivedCount' => Patient::onlyTrashed()->count(),
        ]);
    }

    // The archive ("recycle bin") — patients that have been archived.
    public function archived()
    {
        return view('patients.archived', [
            'patients' => Patient::onlyTrashed()->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('patients.form', [
            'patient' => new Patient(), 'action' => route('patients.store'), 'method' => 'POST',
        ]);
    }

    public function store(Request $request)
    {
        Patient::create($this->data($request));

        return redirect()->route('patients.index')->with('flash', ['type' => 'success', 'message' => 'Patient created.']);
    }

    public function edit(Patient $patient)
    {
        return view('patients.form', [
            'patient' => $patient, 'action' => route('patients.update', $patient), 'method' => 'PUT',
        ]);
    }

    public function update(Request $request, Patient $patient)
    {
        $patient->update($this->data($request));

        return redirect()->route('patients.index')->with('flash', ['type' => 'success', 'message' => 'Patient updated.']);
    }

    // ARCHIVE (soft delete): hides the patient but keeps all their history.
    // Always succeeds — it doesn't touch the invoices/appointments that would
    // otherwise block a hard delete.
    public function destroy(Patient $patient)
    {
        $patient->delete();

        return redirect()->route('patients.index')->with('flash', ['type' => 'success', 'message' => 'Patient archived. Find them under "Archived patients" to restore or permanently delete.']);
    }

    // RESTORE: bring an archived patient back to the active list.
    public function restore(string $id)
    {
        Patient::onlyTrashed()->findOrFail($id)->restore();

        return redirect()->route('patients.archived')->with('flash', ['type' => 'success', 'message' => 'Patient restored.']);
    }

    // PERMANENT DELETE: really remove the patient AND all of their records —
    // invoices, payments, appointments, treatments, notes, lab cases, media and
    // their login. This erases financial history and cannot be undone; the UI
    // gates it behind a strong confirmation. We delete children in an order that
    // satisfies the foreign keys, all inside one transaction.
    public function forceDelete(string $id)
    {
        $patient = Patient::withTrashed()->findOrFail($id);

        DB::transaction(function () use ($patient) {
            $pid = $patient->id;
            DB::table('invoice')->where('patient_id', $pid)->delete();     // cascades invoice_line + payment_allocation
            DB::table('payment')->where('patient_id', $pid)->delete();     // cascades payment_allocation
            DB::table('treatment')->where('patient_id', $pid)->delete();   // now free of invoice_line references
            DB::table('report')->where('patient_id', $pid)->delete();
            DB::table('appointment')->where('patient_id', $pid)->delete();
            DB::table('lab_case')->where('patient_id', $pid)->delete();
            DB::table('media')->where('patient_id', $pid)->delete();
            $patient->forceDelete();                                       // cascades the patient's account
        });

        return redirect()->route('patients.archived')->with('flash', ['type' => 'success', 'message' => 'Patient and all their records permanently deleted.']);
    }

    private function data(Request $request): array
    {
        return [
            'name' => $request->input('name'),
            'dob' => $request->input('dob') ?: null,
            'phone' => $request->input('phone') ?: null,
            'email' => $request->input('email') ?: null,
        ];
    }
}
