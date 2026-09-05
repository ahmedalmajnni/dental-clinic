<?php

use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\LabCase;
use App\Models\Media;
use App\Services\Billing;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            LabCase::query()->each(function (LabCase $labCase) {
                if (InvoiceLine::where('lab_case_id', $labCase->id)->exists()) {
                    return;
                }

                $invoice = Billing::getOrCreateOpenInvoice($labCase->patient_id);
                InvoiceLine::create([
                    'invoice_id' => $invoice->id,
                    'lab_case_id' => $labCase->id,
                    'description' => 'Lab case: '.$labCase->type,
                    'amount' => Billing::round2((float) $labCase->cost),
                ]);
                Billing::recalcInvoice($invoice->id);
            });

            Media::query()->each(function (Media $media) {
                if (InvoiceLine::where('media_id', $media->id)->exists()) {
                    return;
                }

                $invoice = Billing::getOrCreateOpenInvoice($media->patient_id);
                InvoiceLine::create([
                    'invoice_id' => $invoice->id,
                    'media_id' => $media->id,
                    'description' => 'Media: '.$media->type,
                    'amount' => Billing::round2((float) $media->cost),
                ]);
                Billing::recalcInvoice($invoice->id);
            });
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            InvoiceLine::whereNotNull('lab_case_id')->delete();
            InvoiceLine::whereNotNull('media_id')->delete();
        });
    }
};
