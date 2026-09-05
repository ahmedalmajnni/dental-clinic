<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\InvoiceLine;
use App\Models\Report;
use App\Models\Treatment;
use App\Services\Billing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TreatmentController extends Controller
{
    private const STATUSES = ['planned', 'done', 'cancelled'];

    private function appointmentOptions()
    {
        return Appointment::with('patient')
            ->where('status', 'completed')
            ->orderByDesc('scheduled_at')
            ->get();
    }

    public function index()
    {
        $treatments = Treatment::with('patient')->orderByDesc('created_at')->get();

        return view('treatments.index', compact('treatments'));
    }

    public function create()
    {
        return view('treatments.form', [
            'treatment' => new Treatment(), 'appointments' => $this->appointmentOptions(),
            'statuses' => self::STATUSES, 'action' => route('treatments.store'), 'method' => 'POST',
        ]);
    }

    // THE BILLING HOOK: saving a treatment also creates its invoice line, in one
    // transaction, then refreshes the invoice totals.
    public function store(Request $request)
    {
        $appt = Appointment::whereKey($request->input('appointment_id'))
            ->where('status', 'completed')
            ->first();
        if (! $appt) {
            return back()->withInput()->with('flash', [
                'type' => 'error',
                'message' => 'Please choose a completed appointment.',
            ]);
        }
        $procedure = $request->input('procedure');
        $amount = (float) ($request->input('cost') ?: 0);
        $status = $request->input('status') ?: 'planned';

        DB::transaction(function () use ($appt, $procedure, $amount, $status) {
            $treatment = Treatment::create([
                'appointment_id' => $appt->id, 'patient_id' => $appt->patient_id,
                'procedure' => $procedure, 'cost' => $amount, 'status' => $status,
            ]);
            $invoice = Billing::getOrCreateOpenInvoice($appt->patient_id);
            InvoiceLine::create([
                'invoice_id' => $invoice->id, 'treatment_id' => $treatment->id,
                'description' => $procedure, 'amount' => $amount,
            ]);
            Billing::recalcInvoice($invoice->id);
        });

        return redirect()->route('treatments.index')->with('flash', ['type' => 'success', 'message' => 'Treatment saved and added to the patient invoice.']);
    }

    public function show(Treatment $treatment)
    {
        $treatment->load(['patient', 'appointment.patient', 'appointment.doctor', 'report']);

        return view('treatments.show', [
            'treatment' => $treatment,
            'report' => $treatment->report,
            'appointment' => $treatment->appointment,
        ]);
    }

    public function edit(Treatment $treatment)
    {
        return view('treatments.form', [
            'treatment' => $treatment, 'appointments' => $this->appointmentOptions(),
            'statuses' => self::STATUSES, 'action' => route('treatments.update', $treatment), 'method' => 'PUT',
        ]);
    }

    // Keep the linked invoice line and totals in step with the new cost.
    public function update(Request $request, Treatment $treatment)
    {
        $procedure = $request->input('procedure');
        $amount = (float) ($request->input('cost') ?: 0);
        $status = $request->input('status') ?: 'planned';

        DB::transaction(function () use ($treatment, $procedure, $amount, $status) {
            $treatment->update(['procedure' => $procedure, 'cost' => $amount, 'status' => $status]);
            $line = InvoiceLine::where('treatment_id', $treatment->id)->first();
            if ($line) {
                $line->update(['description' => $procedure, 'amount' => $amount]);
                Billing::recalcInvoice($line->invoice_id);
            }
        });

        return redirect()->route('treatments.index')->with('flash', ['type' => 'success', 'message' => 'Treatment updated.']);
    }

    // Remove the invoice line first (FK is RESTRICT), then recalc.
    public function destroy(Treatment $treatment)
    {
        DB::transaction(function () use ($treatment) {
            $line = InvoiceLine::where('treatment_id', $treatment->id)->first();
            $invoiceId = $line?->invoice_id;
            InvoiceLine::where('treatment_id', $treatment->id)->delete();
            $treatment->delete();
            if ($invoiceId) {
                Billing::recalcInvoice($invoiceId);
            }
        });

        return redirect()->route('treatments.index')->with('flash', ['type' => 'success', 'message' => 'Treatment deleted.']);
    }
}
