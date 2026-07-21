<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Payment;
use App\Models\PaymentAllocation;

/**
 * The single source of truth for invoice/payment math — the PHP equivalent of
 * the Node app's billing.js. Both the Treatments feature (which adds charges)
 * and the Payments feature (which subtracts them) use these methods so the
 * numbers can never drift apart.
 */
class Billing
{
    public static function round2($n): float
    {
        return round((float) $n, 2);
    }

    /** Find the patient's current unpaid invoice, or start a new one. */
    public static function getOrCreateOpenInvoice(string $patientId): Invoice
    {
        $invoice = Invoice::where('patient_id', $patientId)
            ->whereIn('status', ['open', 'partial'])
            ->orderBy('created_at')
            ->first();

        return $invoice ?: Invoice::create([
            'patient_id' => $patientId, 'total' => 0, 'balance' => 0, 'status' => 'open',
        ]);
    }

    /** Recompute an invoice's total, balance and status from lines + payments. */
    public static function recalcInvoice(string $invoiceId): void
    {
        $total = (float) InvoiceLine::where('invoice_id', $invoiceId)->sum('amount');
        $paid = (float) PaymentAllocation::where('invoice_id', $invoiceId)->sum('amount');
        $balance = max(self::round2($total - $paid), 0);

        // Base the status on the BALANCE, not the total. If every line is
        // removed (e.g. its treatment is deleted) while a payment is still
        // allocated, total drops to 0 but the balance is still fully covered
        // (0) — that invoice is correctly "paid", not "partial". Using total
        // for this check used to mislabel it as partial forever.
        $status = 'open';
        if ($balance <= 0 && $paid > 0) {
            $status = 'paid';
        } elseif ($paid > 0) {
            $status = 'partial';
        }

        Invoice::where('id', $invoiceId)->update([
            'total' => self::round2($total), 'balance' => $balance, 'status' => $status,
        ]);
    }

    /** How much of a payment has already been applied to invoices. */
    public static function paymentAllocated(string $paymentId): float
    {
        return self::round2((float) PaymentAllocation::where('payment_id', $paymentId)->sum('amount'));
    }

    /**
     * Apply part of a payment to one invoice. Caps at both the invoice balance
     * and the payment's unallocated remainder, so you can never over-pay a bill
     * or over-spend a payment. Returns the amount actually applied.
     */
    public static function allocateToInvoice(string $paymentId, string $invoiceId, float $requested): float
    {
        $payment = Payment::findOrFail($paymentId);
        $invoice = Invoice::findOrFail($invoiceId);

        $left = self::round2((float) $payment->amount - self::paymentAllocated($paymentId));
        $amount = self::round2(min($requested, $left, (float) $invoice->balance));
        if ($amount <= 0) {
            return 0;
        }

        PaymentAllocation::create([
            'payment_id' => $paymentId, 'invoice_id' => $invoiceId, 'amount' => $amount,
        ]);
        self::recalcInvoice($invoiceId);

        return $amount;
    }

    /**
     * Spread a payment across the patient's unpaid invoices, oldest first.
     * Returns ['applied' => x, 'leftover' => y] where leftover is patient credit.
     */
    public static function autoAllocate(string $paymentId, string $patientId): array
    {
        $invoices = Invoice::where('patient_id', $patientId)
            ->whereIn('status', ['open', 'partial'])
            ->where('balance', '>', 0)
            ->orderBy('created_at')
            ->get();

        $applied = 0.0;
        foreach ($invoices as $inv) {
            $payment = Payment::findOrFail($paymentId);
            $remaining = self::round2((float) $payment->amount - self::paymentAllocated($paymentId));
            if ($remaining <= 0) {
                break;
            }
            $applied += self::allocateToInvoice($paymentId, $inv->id, $remaining);
        }

        $payment = Payment::findOrFail($paymentId);
        $leftover = self::round2((float) $payment->amount - self::paymentAllocated($paymentId));

        return ['applied' => self::round2($applied), 'leftover' => $leftover];
    }
}
