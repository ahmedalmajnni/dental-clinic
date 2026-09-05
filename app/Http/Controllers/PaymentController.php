<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Services\Billing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    private const METHODS = ['cash', 'card', 'transfer', 'other'];

    // One row per patient who has payments. Archived patients are excluded
    // automatically (Patient queries skip soft-deleted rows), so their payments
    // are hidden along with their file — and reappear if the patient is restored.
    public function index()
    {
        $patients = Patient::whereHas('payments')
            ->withCount('payments')
            ->withSum('payments', 'amount')
            ->orderBy('name')
            ->get();

        // Work out each patient's unallocated credit (total paid − total applied).
        $patients->each(function ($p) {
            $paymentIds = Payment::where('patient_id', $p->id)->pluck('id');
            $allocated = (float) PaymentAllocation::whereIn('payment_id', $paymentIds)->sum('amount');
            $p->total_paid = (float) $p->payments_sum_amount;
            $p->credit = round($p->total_paid - $allocated, 2);
        });

        return view('payments.index', compact('patients'));
    }

    // Drill-down: the individual payments for one patient.
    public function forPatient(Patient $patient)
    {
        $payments = Payment::where('patient_id', $patient->id)
            ->orderByDesc('paid_at')->get()->map(function ($p) {
                $p->allocated = (float) PaymentAllocation::where('payment_id', $p->id)->sum('amount');
                $p->unallocated = round((float) $p->amount - $p->allocated, 2);

                return $p;
            });

        return view('payments.patient', compact('patient', 'payments'));
    }

    public function create()
    {
        return view('payments.form', [
            'patients' => $this->patientsWithBalance(), 'methods' => self::METHODS,
            'payment' => null, 'action' => route('payments.store'), 'method' => 'POST',
        ]);
    }

    public function edit(Payment $payment)
    {
        return view('payments.form', [
            'patients' => collect(), 'methods' => self::METHODS,
            'payment' => $payment, 'action' => route('payments.update', $payment), 'method' => 'PUT',
        ]);
    }

    public function store(Request $request)
    {
        $patientId = $request->input('patient_id');
        $amount = (float) $request->input('amount');

        if (! $patientId || $amount <= 0) {
            return back()->withInput()->with('flash', ['type' => 'error', 'message' => 'Choose a patient and enter an amount greater than zero.']);
        }

        $result = DB::transaction(function () use ($request, $patientId, $amount) {
            $payment = Payment::create([
                'patient_id' => $patientId, 'amount' => $amount,
                'method' => $request->input('method') ?: 'cash',
                'paid_at' => $request->input('paid_at') ?: now(),
            ]);

            return Billing::autoAllocate($payment->id, $patientId);
        });

        $msg = 'Payment of $'.number_format($amount, 2).' recorded. Applied $'.number_format($result['applied'], 2).' to invoices.';
        if ($result['leftover'] > 0) {
            $msg .= ' $'.number_format($result['leftover'], 2).' left as patient credit.';
        }

        return redirect()->route('payments.index')->with('flash', ['type' => 'success', 'message' => $msg]);
    }

    public function update(Request $request, Payment $payment)
    {
        $amount = (float) $request->input('amount');
        if ($amount <= 0) {
            return back()->withInput()->with('flash', ['type' => 'error', 'message' => 'Enter an amount greater than zero.']);
        }

        DB::transaction(function () use ($request, $payment, $amount) {
            $invoiceIds = PaymentAllocation::where('payment_id', $payment->id)
                ->pluck('invoice_id')->unique();
            PaymentAllocation::where('payment_id', $payment->id)->delete();
            $payment->update([
                'amount' => $amount,
                'method' => $request->input('method') ?: 'cash',
                'paid_at' => $request->input('paid_at') ?: $payment->paid_at,
            ]);

            foreach ($invoiceIds as $invoiceId) {
                Billing::recalcInvoice($invoiceId);
            }
            Billing::autoAllocate($payment->id, $payment->patient_id);
        });

        return redirect()->route('payments.patient', $payment->patient_id)
            ->with('flash', ['type' => 'success', 'message' => 'Payment updated and invoice balances recalculated.']);
    }

    private function patientsWithBalance()
    {
        return Patient::orderBy('name')->get()->map(function ($p) {
            $p->owed = (float) Invoice::where('patient_id', $p->id)
                ->whereIn('status', ['open', 'partial'])->sum('balance');

            return $p;
        });
    }
}
