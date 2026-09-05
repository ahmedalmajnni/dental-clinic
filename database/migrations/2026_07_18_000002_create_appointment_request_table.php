<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Patient appointment requests. A patient asks for an appointment with a chosen
 * doctor; staff then either schedule it (creating a real
 * appointment and linking it here) or decline it. The patient sees the outcome
 * in their account.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE TABLE appointment_request (
                id             UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                patient_id     UUID NOT NULL REFERENCES patient(id)  ON DELETE CASCADE,
                doctor_id      UUID NOT NULL REFERENCES employee(id) ON DELETE RESTRICT,
                preferred_date DATE,
                note           TEXT,
                status         VARCHAR(20) NOT NULL DEFAULT 'pending'
                                   CHECK (status IN ('pending','scheduled','declined','cancelled')),
                appointment_id UUID REFERENCES appointment(id) ON DELETE SET NULL,
                response_note  TEXT,
                processed_by   UUID REFERENCES employee(id) ON DELETE SET NULL,
                processed_at   TIMESTAMPTZ,
                created_at     TIMESTAMPTZ NOT NULL DEFAULT now()
            );
            CREATE INDEX idx_apptreq_patient ON appointment_request(patient_id);
            CREATE INDEX idx_apptreq_status  ON appointment_request(status);
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TABLE IF EXISTS appointment_request;');
    }
};
