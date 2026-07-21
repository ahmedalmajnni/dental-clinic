<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Appointment;
use App\Models\AppointmentRequest;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\LabCase;
use App\Models\Media;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Report;
use App\Models\Treatment;
use App\Services\Billing;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Gives every table a proper set of default data.
 *
 * For each table it "tops up" to a target number of rows: if you already have
 * records they are kept and it just adds what's missing. Nothing is deleted or
 * overwritten, and running it twice is safe.
 *
 * Treatments and payments go through the app's own Billing service, so invoice
 * totals, balances and statuses are genuinely correct.
 *
 * Run with:  php artisan db:seed --class=DemoDataSeeder
 */
class DemoDataSeeder extends Seeder
{
    /** Password given to every demo login this seeder creates. */
    private const DEMO_PASSWORD = 'password123';

    /** How many rows each table should end up with (at minimum). */
    private const TARGETS = [
        'branch' => 3, 'employee' => 5, 'patient' => 5, 'appointment' => 6,
        'report' => 4, 'treatment' => 5, 'payment' => 3,
        'lab_case' => 4, 'media' => 4, 'appointment_request' => 3,
    ];

    public function run(): void
    {
        $summary = [];

        // ---- 1. Branches ----------------------------------------------------
        $branchSeed = [
            ['name' => 'Main Clinic', 'type' => 'clinic', 'phone' => '011-000-0001', 'address' => 'City centre'],
            ['name' => 'North Branch', 'type' => 'clinic', 'phone' => '011-000-0002', 'address' => 'North district'],
            ['name' => 'Photo Studio', 'type' => 'studio', 'phone' => '011-000-0003', 'address' => 'Next to Main Clinic'],
        ];
        $summary['branch'] = $this->topUp('branch', Branch::count(), self::TARGETS['branch'],
            fn ($i) => Branch::create($branchSeed[$i % count($branchSeed)]));
        $branches = Branch::orderBy('created_at')->get();

        // ---- 2. Employees ---------------------------------------------------
        $employeeSeed = [
            ['name' => 'Dr Adam Hart', 'job_title' => 'doctor', 'phone' => '011-111-0001'],
            ['name' => 'Dr Lina Fares', 'job_title' => 'doctor', 'phone' => '011-111-0002'],
            ['name' => 'Rana Reception', 'job_title' => 'reception', 'phone' => '011-111-0003'],
            ['name' => 'Sami Lab', 'job_title' => 'lab_tech', 'phone' => '011-111-0004'],
        ];
        $summary['employee'] = $this->topUp('employee', Employee::count(), self::TARGETS['employee'],
            function ($i) use ($employeeSeed, $branches) {
                $row = $employeeSeed[$i % count($employeeSeed)];
                $row['branch_id'] = $branches[$i % $branches->count()]->id;

                return Employee::create($row);
            });

        $doctors = Employee::where('job_title', 'doctor')->get();
        if ($doctors->isEmpty()) {   // guarantee at least one doctor
            $doctors = collect([Employee::create([
                'branch_id' => $branches->first()->id, 'name' => 'Dr Demo', 'job_title' => 'doctor',
            ])]);
        }

        // ---- 3. Patients ----------------------------------------------------
        $patientSeed = [
            ['name' => 'Sample Patient One', 'dob' => '1990-04-12', 'phone' => '0900-000-001', 'email' => 'patient1@example.com'],
            ['name' => 'Sample Patient Two', 'dob' => '1985-11-03', 'phone' => '0900-000-002', 'email' => 'patient2@example.com'],
            ['name' => 'Sample Patient Three', 'dob' => '2000-07-21', 'phone' => '0900-000-003', 'email' => 'patient3@example.com'],
            ['name' => 'Sample Patient Four', 'dob' => '1978-01-30', 'phone' => '0900-000-004', 'email' => 'patient4@example.com'],
            ['name' => 'Sample Patient Five', 'dob' => '1995-09-09', 'phone' => '0900-000-005', 'email' => 'patient5@example.com'],
        ];
        $summary['patient'] = $this->topUp('patient', Patient::count(), self::TARGETS['patient'],
            fn ($i) => Patient::create($patientSeed[$i % count($patientSeed)]));
        $patients = Patient::orderBy('created_at')->get();

        // ---- 4. Appointments -------------------------------------------------
        $summary['appointment'] = $this->topUp('appointment', Appointment::count(), self::TARGETS['appointment'],
            function ($i) use ($patients, $doctors) {
                $doctor = $doctors[$i % $doctors->count()];

                return Appointment::create([
                    'patient_id' => $patients[$i % $patients->count()]->id,
                    'doctor_id' => $doctor->id,
                    'branch_id' => $doctor->branch_id,
                    'scheduled_at' => now()->addDays($i + 1)->setTime(9 + ($i % 8), 0),
                    'status' => ['booked', 'completed', 'booked'][$i % 3],
                ]);
            });

        // ---- 5. Clinical notes -------------------------------------------------
        $noteSeed = [
            ['Caries in lower left first molar', 'Cavity cleaned and filled. Advised fluoride toothpaste.'],
            ['Generalised plaque and mild gingivitis', 'Full scaling done. Recommended daily flossing.'],
            ['Irreversible pulpitis, upper right premolar', 'Root canal planned over two visits.'],
            ['Impacted lower third molar', 'Referred for surgical extraction.'],
        ];
        $summary['report'] = $this->topUp('report', Report::count(), self::TARGETS['report'],
            function ($i) use ($noteSeed) {
                // Prefer an appointment that has no note yet.
                $appt = Appointment::whereNotIn('id', Report::pluck('appointment_id'))->inRandomOrder()->first()
                    ?? Appointment::inRandomOrder()->first();
                $row = $noteSeed[$i % count($noteSeed)];

                return Report::create([
                    'appointment_id' => $appt->id, 'patient_id' => $appt->patient_id,
                    'doctor_id' => $appt->doctor_id, 'diagnosis' => $row[0], 'notes' => $row[1],
                    'next_visit' => now()->addMonths(6)->toDateString(),
                ]);
            });

        // ---- 6. Treatments (each adds an invoice line to the patient's bill) ---
        $procedureSeed = [
            ['Composite filling', 150, 'done'],
            ['Scaling and polishing', 80, 'done'],
            ['Root canal treatment', 400, 'planned'],
            ['Tooth extraction', 120, 'done'],
            ['Crown fitting', 600, 'planned'],
        ];
        $summary['treatment'] = $this->topUp('treatment', Treatment::count(), self::TARGETS['treatment'],
            function ($i) use ($procedureSeed) {
                $appt = Appointment::inRandomOrder()->first();
                [$name, $cost, $status] = $procedureSeed[$i % count($procedureSeed)];

                return DB::transaction(function () use ($appt, $name, $cost, $status) {
                    $treatment = Treatment::create([
                        'appointment_id' => $appt->id, 'patient_id' => $appt->patient_id,
                        'procedure' => $name, 'cost' => $cost, 'status' => $status,
                    ]);
                    $invoice = Billing::getOrCreateOpenInvoice($appt->patient_id);
                    InvoiceLine::create([
                        'invoice_id' => $invoice->id, 'treatment_id' => $treatment->id,
                        'description' => $name, 'amount' => $cost,
                    ]);
                    Billing::recalcInvoice($invoice->id);

                    return $treatment;
                });
            });

        // ---- 7. Payments (auto-allocated -> fills payment_allocation) ----------
        $summary['payment'] = $this->topUp('payment', Payment::count(), self::TARGETS['payment'],
            function ($i) use ($patients) {
                // Pay against a bill that still owes money, else record a credit.
                $invoice = Invoice::where('balance', '>', 0)->orderBy('created_at')->first();
                $patient = $invoice?->patient ?? $patients[$i % $patients->count()];
                $amount = $invoice ? round((float) $invoice->balance * ($i === 0 ? 0.5 : 1.0), 2) : 100.00;
                $amount = max($amount, 1);

                return DB::transaction(function () use ($patient, $amount, $i) {
                    $payment = Payment::create([
                        'patient_id' => $patient->id, 'amount' => $amount,
                        'method' => ['cash', 'card', 'transfer'][$i % 3], 'paid_at' => now()->subDays($i),
                    ]);
                    Billing::autoAllocate($payment->id, $patient->id);

                    return $payment;
                });
            });

        // ---- 7b. Make sure at least one bill is still outstanding ---------------
        // Payments above can clear every invoice, which makes for a dull demo:
        // no "Outstanding" figure and nothing to record a payment against.
        if (Invoice::where('balance', '>', 0)->count() === 0) {
            $appt = Appointment::inRandomOrder()->first();
            DB::transaction(function () use ($appt) {
                $treatment = Treatment::create([
                    'appointment_id' => $appt->id, 'patient_id' => $appt->patient_id,
                    'procedure' => 'Teeth whitening', 'cost' => 220, 'status' => 'planned',
                ]);
                $invoice = Billing::getOrCreateOpenInvoice($appt->patient_id);
                InvoiceLine::create([
                    'invoice_id' => $invoice->id, 'treatment_id' => $treatment->id,
                    'description' => 'Teeth whitening', 'amount' => 220,
                ]);
                Billing::recalcInvoice($invoice->id);
            });
            $summary['treatment']++;
        }

        // ---- 8. Lab cases -------------------------------------------------------
        $labSeed = [
            ['Crown', 'in_progress', 250, 14], ['Bridge', 'ready', 500, 7],
            ['Denture', 'received', 350, 30], ['Veneer', 'delivered', 300, 3],
        ];
        $summary['lab_case'] = $this->topUp('lab_case', LabCase::count(), self::TARGETS['lab_case'],
            function ($i) use ($labSeed, $patients, $doctors) {
                $row = $labSeed[$i % count($labSeed)];

                return LabCase::create([
                    'patient_id' => $patients[$i % $patients->count()]->id,
                    'doctor_id' => $doctors[$i % $doctors->count()]->id,
                    'type' => $row[0], 'status' => $row[1], 'cost' => $row[2],
                    'due_date' => now()->addDays($row[3])->toDateString(),
                ]);
            });

        // ---- 9. Media ------------------------------------------------------------
        $mediaSeed = [
            ['xray', 'diagnostic', 'https://example.com/media/xray-001.jpg'],
            ['photo', 'before', 'https://example.com/media/before-002.jpg'],
            ['scan', 'intraoral', 'https://example.com/media/scan-003.jpg'],
            ['photo', 'after', 'https://example.com/media/after-004.jpg'],
        ];
        $summary['media'] = $this->topUp('media', Media::count(), self::TARGETS['media'],
            function ($i) use ($mediaSeed, $patients, $branches) {
                $row = $mediaSeed[$i % count($mediaSeed)];

                return Media::create([
                    'patient_id' => $patients[$i % $patients->count()]->id,
                    'branch_id' => $branches[$i % $branches->count()]->id,
                    'type' => $row[0], 'category' => $row[1], 'file_url' => $row[2],
                    'taken_at' => now()->subDays($i + 1),
                ]);
            });

        // ---- 10. Appointment requests --------------------------------------------
        $summary['appointment_request'] = $this->topUp('appointment_request', AppointmentRequest::count(), self::TARGETS['appointment_request'],
            function ($i) use ($patients, $doctors) {
                $doctor = $doctors[$i % $doctors->count()];

                return AppointmentRequest::create([
                    'patient_id' => $patients[$i % $patients->count()]->id,
                    'doctor_id' => $doctor->id,
                    'branch_id' => $doctor->branch_id,   // always the doctor's own specialty
                    'preferred_date' => now()->addDays(7 + $i)->toDateString(),
                    'note' => ['Mornings suit me best.', 'Any afternoon is fine.', 'As soon as possible please.'][$i % 3],
                    'status' => 'pending',
                ]);
            });

        // ---- 11. Logins, so you can sign in as each demo doctor / patient ---------
        $added = 0;
        foreach (Employee::doesntHave('account')->get() as $employee) {
            Account::create([
                'email' => $this->uniqueEmail($employee->name, 'clinic.local'),
                'password_hash' => Hash::make(self::DEMO_PASSWORD),
                'role' => $employee->job_title === 'admin' ? 'admin' : 'employee',
                'employee_id' => $employee->id,
                'is_active' => true,
            ]);
            $added++;
        }
        foreach (Patient::doesntHave('account')->get() as $patient) {
            $email = ($patient->email && ! Account::where('email', $patient->email)->exists())
                ? $patient->email
                : $this->uniqueEmail($patient->name, 'example.com');
            Account::create([
                'email' => $email,
                'password_hash' => Hash::make(self::DEMO_PASSWORD),
                'role' => 'patient',
                'patient_id' => $patient->id,
                'is_active' => true,
            ]);
            $added++;
        }
        $summary['account'] = $added;

        // ---- Report what happened ------------------------------------------------
        foreach ($summary as $table => $count) {
            $this->command->info(sprintf('%-20s +%d added', $table, $count));
        }
        $this->command->warn('Demo logins use the password: '.self::DEMO_PASSWORD);
    }

    /** Build a login email from a name, guaranteeing it is unique. */
    private function uniqueEmail(string $name, string $domain): string
    {
        $base = Str::slug($name, '.') ?: 'user';
        $email = $base.'@'.$domain;
        $n = 2;
        while (Account::where('email', $email)->exists()) {
            $email = $base.$n.'@'.$domain;
            $n++;
        }

        return $email;
    }

    /** Create rows until the table reaches $target. Returns how many were added. */
    private function topUp(string $table, int $current, int $target, callable $make): int
    {
        $added = 0;
        for ($i = $current; $i < $target; $i++) {
            $make($i);
            $added++;
        }

        return $added;
    }
}
