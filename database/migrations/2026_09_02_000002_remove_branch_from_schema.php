<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE appointment_request DROP CONSTRAINT IF EXISTS appointment_request_branch_id_foreign');
        DB::statement('ALTER TABLE appointment DROP CONSTRAINT IF EXISTS appointment_branch_id_foreign');
        DB::statement('ALTER TABLE media DROP CONSTRAINT IF EXISTS media_branch_id_foreign');
        DB::statement('ALTER TABLE employee DROP CONSTRAINT IF EXISTS employee_branch_id_foreign');
        DB::statement('DROP INDEX IF EXISTS idx_apptreq_branch');
        DB::statement('DROP INDEX IF EXISTS idx_appt_branch');
        DB::statement('DROP INDEX IF EXISTS idx_media_branch');
        DB::statement('DROP INDEX IF EXISTS idx_employee_branch');
        DB::statement('ALTER TABLE appointment_request DROP COLUMN IF EXISTS branch_id');
        DB::statement('ALTER TABLE appointment DROP COLUMN IF EXISTS branch_id');
        DB::statement('ALTER TABLE media DROP COLUMN IF EXISTS branch_id');
        DB::statement('ALTER TABLE employee DROP COLUMN IF EXISTS branch_id');
        DB::statement('DROP TABLE IF EXISTS branch CASCADE');
    }

    public function down(): void
    {
        // Branch data is intentionally removed and cannot be restored automatically.
    }
};
