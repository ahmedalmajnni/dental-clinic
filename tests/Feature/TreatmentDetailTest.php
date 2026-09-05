<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TreatmentDetailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('employee', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('job_title');
        });

        Schema::create('patient', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->nullable();
        });

        Schema::create('account', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email')->unique();
            $table->string('password_hash');
            $table->string('role');
            $table->uuid('employee_id')->nullable();
            $table->uuid('patient_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('last_login')->nullable();
            $table->dateTime('created_at')->nullable();
        });

        Schema::create('appointment', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id');
            $table->uuid('doctor_id');
            $table->dateTime('scheduled_at');
            $table->string('status')->default('booked');
        });

        Schema::create('report', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('appointment_id');
            $table->uuid('patient_id');
            $table->uuid('doctor_id');
            $table->text('diagnosis')->nullable();
            $table->text('notes')->nullable();
            $table->date('next_visit')->nullable();
        });

        Schema::create('treatment', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('appointment_id');
            $table->uuid('patient_id');
            $table->string('procedure');
            $table->decimal('cost', 10, 2)->default(0);
            $table->string('status')->default('planned');
            $table->dateTime('created_at')->nullable();
        });

        $patientId = '11111111-1111-1111-1111-111111111111';
        $doctorId = '22222222-2222-2222-2222-222222222222';
        $appointmentId = '44444444-4444-4444-4444-444444444444';
        $treatmentId = '55555555-5555-5555-5555-555555555555';
        $reportId = '66666666-6666-6666-6666-666666666666';

        \DB::table('employee')->insert(['id' => $doctorId, 'name' => 'Dr. Samer', 'job_title' => 'doctor']);
        \DB::table('patient')->insert(['id' => $patientId, 'name' => 'Khaled Ali', 'email' => 'khaled@example.com']);
        \DB::table('appointment')->insert([
            'id' => $appointmentId,
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'scheduled_at' => '2026-08-28 10:00:00',
            'status' => 'completed',
        ]);
        \DB::table('report')->insert([
            'id' => $reportId,
            'appointment_id' => $appointmentId,
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'diagnosis' => 'Tooth sensitivity and slight decay',
            'notes' => 'Cleaned the cavity, placed a temporary filling, and advised on night guard use.',
            'next_visit' => '2026-09-04',
        ]);
        \DB::table('treatment')->insert([
            'id' => $treatmentId,
            'appointment_id' => $appointmentId,
            'patient_id' => $patientId,
            'procedure' => 'Cavity filling',
            'cost' => 120.00,
            'status' => 'done',
            'created_at' => '2026-08-28 10:30:00',
        ]);
    }

    public function test_treatment_procedure_links_to_case_report(): void
    {
        $account = \App\Models\Account::create([
            'email' => 'admin@example.com',
            'password_hash' => bcrypt('secret123'),
            'role' => 'admin',
            'employee_id' => '22222222-2222-2222-2222-222222222222',
            'patient_id' => null,
            'is_active' => true,
        ]);

        $this->actingAs($account, 'web');

        $treatment = \App\Models\Treatment::first();

        $response = $this->get('/treatments/'.$treatment->id);

        $response->assertOk();
        $response->assertSee('Case report');
        $response->assertSee('Cavity filling');
        $response->assertSee('Tooth sensitivity and slight decay');
        $response->assertSee('temporary filling');
    }
}
