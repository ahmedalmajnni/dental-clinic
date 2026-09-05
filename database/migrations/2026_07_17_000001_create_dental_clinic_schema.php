<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Creates the 13 dental-clinic tables by running the original PostgreSQL
 * schema file verbatim. Using the raw SQL (instead of hand-translating to
 * Laravel's Schema builder) guarantees the tables are identical to the
 * hand-written design — UUID defaults, CHECK constraints, indexes and all.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sql = file_get_contents(database_path('sql/dental_clinic_schema.sql'));
        DB::unprepared($sql);
    }

    public function down(): void
    {
        DB::unprepared(
            'DROP TABLE IF EXISTS media, lab_case, payment_allocation, payment, '
            . 'invoice_line, invoice, treatment, report, appointment, account, '
            . 'patient, employee CASCADE;'
        );
    }
};
