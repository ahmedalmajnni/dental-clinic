<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * When each doctor is available for appointments. `doctor_availability` holds the
 * ordinary weekly pattern (a weekday plus a time range and how long one slot is);
 * `doctor_time_off` holds the exceptions to it for a single date — a whole or
 * partial day off, or one-off extra hours. Booking reads both and offers only the
 * slots that fall inside a window and are not already taken.
 *
 * The date column is `on_date` rather than `date` so queries never have to quote it.
 * Weekday numbering matches Carbon's dayOfWeek: 0 = Sunday ... 6 = Saturday.
 *
 * Existing doctors are backfilled with default hours, otherwise every one of them
 * would become unbookable the moment this feature goes live.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE TABLE doctor_availability (
                id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                doctor_id    UUID NOT NULL REFERENCES employee(id) ON DELETE CASCADE,
                weekday      SMALLINT NOT NULL CHECK (weekday BETWEEN 0 AND 6),
                start_time   TIME NOT NULL,
                end_time     TIME NOT NULL,
                slot_minutes SMALLINT NOT NULL DEFAULT 30 CHECK (slot_minutes BETWEEN 5 AND 240),
                created_at   TIMESTAMPTZ NOT NULL DEFAULT now(),
                CONSTRAINT doctor_availability_range CHECK (end_time > start_time)
            );
            CREATE INDEX idx_docavail_doctor ON doctor_availability(doctor_id, weekday);

            CREATE TABLE doctor_time_off (
                id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                doctor_id    UUID NOT NULL REFERENCES employee(id) ON DELETE CASCADE,
                on_date      DATE NOT NULL,
                kind         VARCHAR(10) NOT NULL DEFAULT 'off' CHECK (kind IN ('off','extra')),
                start_time   TIME,
                end_time     TIME,
                slot_minutes SMALLINT,
                reason       VARCHAR(255),
                created_at   TIMESTAMPTZ NOT NULL DEFAULT now(),
                CONSTRAINT doctor_time_off_range CHECK (
                    (start_time IS NULL AND end_time IS NULL)
                    OR (start_time IS NOT NULL AND end_time IS NOT NULL AND end_time > start_time)
                ),
                CONSTRAINT doctor_time_off_extra_needs_range CHECK (
                    kind <> 'extra' OR (start_time IS NOT NULL AND end_time IS NOT NULL)
                )
            );
            CREATE INDEX idx_doctimeoff_doctor_date ON doctor_time_off(doctor_id, on_date);

            -- Sunday-Thursday mornings and evenings, the clinic's usual shape.
            INSERT INTO doctor_availability (doctor_id, weekday, start_time, end_time, slot_minutes)
            SELECT e.id, d.weekday, w.start_time, w.end_time, 30
            FROM employee e
            CROSS JOIN generate_series(0, 4) AS d(weekday)
            CROSS JOIN (VALUES (TIME '09:00', TIME '13:00'),
                               (TIME '16:00', TIME '20:00')) AS w(start_time, end_time)
            WHERE e.job_title = 'doctor';
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TABLE IF EXISTS doctor_time_off, doctor_availability;');
    }
};
