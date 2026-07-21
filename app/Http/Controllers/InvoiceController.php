<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Services\Billing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    private const METHODS = ['cash', 'card', 'transfer', 'other'];

    public function index()
    {
        $invoices = Invoice::with('patient')->orderByDesc('created_at')->get();

        return view('invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load('patient');
        $lines = InvoiceLine::with('treatment')->where('invoice_id', $invoice->id)
            ->get()->sortBy(fn ($l) => optional($l->treatment)->created_at)->values();
        $payments = PaymentAllocation::with('payment')->where('invoice_id', $invoice->id)
            ->get()->sortBy(fn ($a) => optional($a->payment)->paid_at)->values();

        return view('invoices.show', ['invoice' => $invoice, 'lines' => $lines, 'payments' => $payments, 'methods' => self::METHODS]);
    }

    // Record a payment for THIS invoice only.
    public function pay(Request $request, Invoice $invoice)
    {
        $amount = (float) $request->input('amount');
        if ($amount <= 0) {
            return back()->with('flash', ['type' => 'error', 'message' => 'Enter a payment amount greater than zero.']);
        }
        if ($amount > (float) $invoice->balance) {
            return back()->with('flash', ['type' => 'error', 'message' => 'Amount is more than the balance ($'.number_format((float) $invoice->balance, 2).'). Use the Payments screen to record a credit.']);
        }

        DB::transaction(function () use ($request, $invoice, $amount) {
            $payment = Payment::create([
                'patient_id' => $invoice->patient_id, 'amount' => $amount,
                'method' => $request->input('method') ?: 'cash',
                'paid_at' => $request->input('paid_at') ?: now(),
            ]);
            Billing::allocateToInvoice($payment->id, $invoice->id, $amount);
        });

        return redirect()->route('invoices.show', $invoice)->with('flash', ['type' => 'success', 'message' => 'Payment of $'.number_format($amount, 2).' recorded.']);
    }
}
