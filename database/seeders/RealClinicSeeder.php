<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Appointment;
use App\Models\AppointmentRequest;
use App\Models\Branch;
use App\Models\DoctorAvailability;
use App\Models\DoctorTimeOff;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\LabCase;
use App\Models\Media;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Report;
use App\Models\Treatment;
use App\Services\Billing;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Replaces the "Sample Patient One" demo fixtures with a plausible working
 * clinic: real-looking names, Aleppo phone numbers, and a book of business
 * that actually adds up.
 *
 * Everything clinical is wiped first. The admin login is deliberately kept —
 * deleting it would lock you out — and is moved onto the new main branch so the
 * old demo branches can be removed.
 *
 * Money is produced through App\Services\Billing rather than written by hand,
 * so invoice totals, balances and statuses match what the app itself would
 * calculate.
 *
 * Run with:  php artisan db:seed --class=RealClinicSeeder
 */
class RealClinicSeeder extends Seeder
{
    private const STAFF_PASSWORD = 'clinic2026';

    public function run(): void
    {
        DB::transaction(function () {
            $admin = $this->wipe();
            $branches = $this->branches($admin);
            $staff = $this->staff($branches);
            $this->availability($staff);
            $patients = $this->patients();
            $this->clinicalHistory($patients, $staff, $branches);
        });

        $this->command->info('');
        $this->command->info('Clinic data rebuilt.');
        $this->command->warn('Staff logins use the password: '.self::STAFF_PASSWORD);
        $this->command->warn('Your admin login is unchanged — change its password before going live.');
    }

    /**
     * Clear the demo data, keeping only the admin account. Order matters: every
     * foreign key here is ON DELETE RESTRICT, so children go before parents.
     */
    private function wipe(): Account
    {
        $admin = Account::where('role', 'admin')->orderBy('created_at')->first();
        if (! $admin) {
            throw new \RuntimeException('No admin account found — refusing to wipe, you would be locked out.');
        }

        PaymentAllocation::query()->delete();
        Payment::query()->delete();
        InvoiceLine::query()->delete();
        Invoice::query()->delete();
        Report::query()->delete();
        Treatment::query()->delete();
        Media::query()->delete();
        LabCase::query()->delete();
        AppointmentRequest::query()->delete();
        Appointment::query()->delete();
        DoctorTimeOff::query()->delete();
        DoctorAvailability::query()->delete();

        // Patients are soft-deleted, so clear the archive too.
        Patient::withTrashed()->forceDelete();

        // Every other login goes; deleting an employee cascades its account.
        Account::where('id', '!=', $admin->id)->whereNotNull('patient_id')->delete();
        Employee::where('id', '!=', $admin->employee_id)->delete();

        return $admin;
    }

    /** Four clinic branches, each with a specialist doctor. */
    private function branches(Account $admin): array
    {
        $main = Branch::create([
            'name' => 'Main Clinic — Aleppo Center',
            'type' => 'clinic',
            'phone' => '+963 21 613 2200',
            'address' => 'Al-Jdeideh, Aleppo',
        ]);
        $second = Branch::create([
            'name' => 'Azizieh Branch',
            'type' => 'clinic',
            'phone' => '+963 21 331 4870',
            'address' => 'Al-Azizieh, Aleppo',
        ]);
        $studio = Branch::create([
            'name' => 'Imaging & Diagnostics Branch',
            'type' => 'clinic',
            'phone' => '+963 21 613 2211',
            'address' => 'Al-Jdeideh, Aleppo (ground floor)',
        ]);
        $fourth = Branch::create([
            'name' => 'Midan Branch',
            'type' => 'clinic',
            'phone' => '+963 21 445 3020',
            'address' => 'Al-Midan, Aleppo',
        ]);

        // Move the admin onto the new main branch so the demo ones can go.
        Employee::whereKey($admin->employee_id)->update(['branch_id' => $main->id]);
        Branch::whereNotIn('id', [$main->id, $second->id, $studio->id, $fourth->id])->delete();

        return ['main' => $main, 'second' => $second, 'studio' => $studio, 'fourth' => $fourth];
    }

    /** Three dentists, two receptionists and a lab technician. */
    private function staff(array $branches): array
    {
        $people = [
            'nour' => ['Dr. Nour Haddad', 'doctor', 'main', '+963 933 214 776', 'General dentistry'],
            'karim' => ['Dr. Karim Nasser', 'doctor', 'main', '+963 944 108 235', 'Endodontics'],
            'rana' => ['Dr. Rana Khoury', 'doctor', 'second', '+963 991 663 402', 'Cosmetic dentistry'],
            'hadi' => ['Dr. Hadi Saleh', 'doctor', 'studio', '+963 977 512 408', 'Oral radiology'],
            'lina' => ['Lina Saadeh', 'reception', 'main', '+963 933 770 118', null],
            'maya' => ['Maya Dib', 'reception', 'second', '+963 955 401 927', null],
            'omar' => ['Omar Fares', 'lab_tech', 'main', '+963 988 315 604', null],
            'samer' => ['Dr. Samer Khalil', 'doctor', 'fourth', '+963 966 430 721', 'Oral surgery'],
        ];

        $staff = [];
        foreach ($people as $key => [$name, $job, $branchKey, $phone, $specialty]) {
            $employee = Employee::create([
                'branch_id' => $branches[$branchKey]->id,
                'name' => $name,
                'job_title' => $job,
                'specialty' => $specialty,
                'phone' => $phone,
            ]);
            Account::create([
                'email' => $this->emailFor($name),
                'password_hash' => Hash::make(self::STAFF_PASSWORD),
                'role' => 'employee',
                'employee_id' => $employee->id,
                'is_active' => true,
            ]);
            $staff[$key] = $employee;
        }

        return $staff;
    }

    /** Working hours, deliberately different per dentist so the screens differ. */
    private function availability(array $staff): void
    {
        // weekday: 0=Sunday .. 6=Saturday. The working week here is Sun-Thu.
        $plans = [
            // Full-timer: mornings and evenings, Sunday to Thursday.
            'nour' => [[0, 1, 2, 3, 4], [['09:00', '13:00', 30], ['16:00', '20:00', 30]]],
            // Surgery lists — longer 45-minute slots, mornings only.
            'karim' => [[0, 2, 4], [['09:30', '14:00', 45]]],
            // Part-time at the second branch, three afternoons.
            'rana' => [[1, 3], [['15:00', '20:00', 30]]],
            'hadi' => [[1, 4], [['09:00', '13:00', 30]]],
            'samer' => [[0, 2, 4], [['14:00', '19:00', 30]]],
        ];

        foreach ($plans as $key => [$weekdays, $windows]) {
            foreach ($weekdays as $weekday) {
                foreach ($windows as [$from, $to, $slot]) {
                    DoctorAvailability::create([
                        'doctor_id' => $staff[$key]->id,
                        'weekday' => $weekday,
                        'start_time' => $from,
                        'end_time' => $to,
                        'slot_minutes' => $slot,
                    ]);
                }
            }
        }

        // A holiday and one extra Saturday clinic, so the exceptions screen has
        // something in it and staff can see what both kinds look like.
        DoctorTimeOff::create([
            'doctor_id' => $staff['nour']->id,
            'on_date' => Carbon::today()->addDays(10)->toDateString(),
            'kind' => 'off',
            'reason' => 'Annual leave',
        ]);
        DoctorTimeOff::create([
            'doctor_id' => $staff['karim']->id,
            'on_date' => Carbon::today()->next(Carbon::SATURDAY)->toDateString(),
            'kind' => 'extra',
            'start_time' => '10:00',
            'end_time' => '14:00',
            'slot_minutes' => 45,
            'reason' => 'Catch-up clinic',
        ]);
    }

    /** A realistic patient list, spread across ages. */
    private function patients(): array
    {
        $rows = [
            ['Ahmad Al-Sayed', '1984-03-17', '+963 933 445 019', 'ahmad.alsayed@example.com'],
            ['Layla Mansour', '1992-11-02', '+963 944 872 130', 'layla.mansour@example.com'],
            ['Hasan Darwish', '1978-06-25', '+963 991 204 558', 'hasan.darwish@example.com'],
            ['Rima Haffar', '2001-01-09', '+963 955 613 744', 'rima.haffar@example.com'],
            ['Yousef Barakat', '1965-09-30', '+963 933 990 267', 'yousef.barakat@example.com'],
            ['Sara Kanaan', '1996-04-14', '+963 988 337 415', 'sara.kanaan@example.com'],
            ['Tarek Othman', '1988-12-08', '+963 944 556 802', 'tarek.othman@example.com'],
            ['Nadia Sleiman', '1973-07-21', '+963 991 748 336', 'nadia.sleiman@example.com'],
            ['Bassel Aziz', '2005-05-03', '+963 955 129 670', 'bassel.aziz@example.com'],
            ['Hala Mroue', '1990-08-27', '+963 933 604 281', 'hala.mroue@example.com'],
            ['Ziad Hamdan', '1959-02-11', '+963 988 415 093', 'ziad.hamdan@example.com'],
            ['Maya Chalhoub', '1999-10-16', '+963 944 730 528', 'maya.chalhoub@example.com'],
        ];

        $patients = [];
        foreach ($rows as [$name, $dob, $phone, $email]) {
            $patient = Patient::create([
                'name' => $name, 'dob' => $dob, 'phone' => $phone, 'email' => $email,
            ]);
            // The first five can sign in and follow their own visits.
            if (count($patients) < 5) {
                Account::create([
                    'email' => $email,
                    'password_hash' => Hash::make('patient2026'),
                    'role' => 'patient',
                    'patient_id' => $patient->id,
                    'is_active' => true,
                ]);
            }
            $patients[] = $patient;
        }

        return $patients;
    }

    /**
     * Past visits with treatments, invoices and payments; today's list; and a
     * forward book. Times land on real availability slots so the calendar and
     * the availability rules agree with each other.
     */
    private function clinicalHistory(array $patients, array $staff, array $branches): void
    {
        $doctors = [$staff['nour'], $staff['karim'], $staff['rana']];

        // --- Completed visits, each with a treatment and an invoice ---
        $done = [
            [0, 'nour', -21, '09:00', 'Scaling and polishing', 45,  'Mild gingivitis, upper molars.', 'Advised interdental brushes; review in six months.'],
            [1, 'nour', -18, '10:30', 'Composite filling', 85,      'Occlusal caries on 36.', 'Single-surface composite placed, no sensitivity after.'],
            [2, 'karim', -14, '09:30', 'Root canal treatment', 320, 'Irreversible pulpitis on 46.', 'Canals obturated. Crown to follow once settled.'],
            [3, 'rana', -12, '15:30', 'Teeth whitening', 260,       'Extrinsic staining, patient requested whitening.', 'Two sessions completed; shade improved by four steps.'],
            [4, 'nour', -9,  '11:00', 'Tooth extraction', 120,      'Non-restorable 17, vertical fracture.', 'Uneventful extraction, sutures not required.'],
            [5, 'karim', -7, '10:15', 'Crown fitting', 480,         'Post-endodontic restoration on 46.', 'Zirconia crown cemented, occlusion checked.'],
            [0, 'nour', -4,  '09:30', 'Check-up', 30,               'Routine six-month review.', 'No new caries. Hygiene much improved.'],
            [6, 'rana', -3,  '16:00', 'Composite filling', 85,      'Cervical abrasion on 23.', 'Restored and polished.'],
        ];

        foreach ($done as [$p, $docKey, $days, $time, $procedure, $cost, $diagnosis, $notes]) {
            $doctor = $staff[$docKey];
            $when = Carbon::today()->addDays($days)->setTimeFromTimeString($time);

            $appointment = Appointment::create([
                'patient_id' => $patients[$p]->id,
                'doctor_id' => $doctor->id,
                'branch_id' => $doctor->branch_id,
                'scheduled_at' => $when,
                'status' => 'completed',
            ]);

            Report::create([
                'appointment_id' => $appointment->id,
                'patient_id' => $patients[$p]->id,
                'doctor_id' => $doctor->id,
                'diagnosis' => $diagnosis,
                'notes' => $notes,
            ]);

            $treatment = Treatment::create([
                'appointment_id' => $appointment->id,
                'patient_id' => $patients[$p]->id,
                'procedure' => $procedure,
                'cost' => $cost,
                'status' => 'done',
            ]);

            // Charge it exactly the way TreatmentController would.
            $invoice = Billing::getOrCreateOpenInvoice($patients[$p]->id);
            InvoiceLine::create([
                'invoice_id' => $invoice->id,
                'treatment_id' => $treatment->id,
                'description' => $procedure,
                'amount' => $cost,
            ]);
            Billing::recalcInvoice($invoice->id);
        }

        // --- Payments: some paid in full, one part-paid, one still owing ---
        $payments = [
            [0, 75, 'cash', -20],
            [1, 85, 'card', -18],
            [2, 200, 'transfer', -13],   // leaves a balance on the root canal
            [3, 260, 'card', -12],
            [5, 480, 'transfer', -6],
        ];
        foreach ($payments as [$p, $amount, $method, $days]) {
            $payment = Payment::create([
                'patient_id' => $patients[$p]->id,
                'amount' => $amount,
                'method' => $method,
                'paid_at' => Carbon::today()->addDays($days)->setTime(12, 0),
            ]);
            Billing::autoAllocate($payment->id, $patients[$p]->id);
        }

        // --- Today and the coming days ---
        $upcoming = [
            [6, 'nour', 0, '09:00', 'booked'],
            [7, 'nour', 0, '10:00', 'booked'],
            [8, 'nour', 0, '16:30', 'booked'],
            [9, 'karim', 1, '09:30', 'booked'],
            [10, 'rana', 1, '15:00', 'booked'],
            [11, 'nour', 2, '11:00', 'booked'],
            [2, 'karim', 3, '10:15', 'booked'],
            [4, 'nour', 4, '17:00', 'booked'],
            [1, 'rana', 6, '16:30', 'booked'],
            [3, 'nour', 7, '09:30', 'booked'],
        ];
        foreach ($upcoming as [$p, $docKey, $days, $time, $status]) {
            $doctor = $staff[$docKey];
            Appointment::create([
                'patient_id' => $patients[$p]->id,
                'doctor_id' => $doctor->id,
                'branch_id' => $doctor->branch_id,
                'scheduled_at' => Carbon::today()->addDays($days)->setTimeFromTimeString($time),
                'status' => $status,
            ]);
        }

        // One no-show and one cancellation, so those statuses are represented.
        Appointment::create([
            'patient_id' => $patients[8]->id, 'doctor_id' => $staff['karim']->id,
            'branch_id' => $staff['karim']->branch_id,
            'scheduled_at' => Carbon::today()->addDays(-5)->setTime(11, 0), 'status' => 'no_show',
        ]);
        Appointment::create([
            'patient_id' => $patients[10]->id, 'doctor_id' => $staff['rana']->id,
            'branch_id' => $staff['rana']->branch_id,
            'scheduled_at' => Carbon::today()->addDays(-2)->setTime(17, 0), 'status' => 'cancelled',
        ]);

        // --- Patient requests waiting on reception ---
        AppointmentRequest::create([
            'patient_id' => $patients[0]->id, 'doctor_id' => $staff['nour']->id,
            'branch_id' => $staff['nour']->branch_id,
            'preferred_date' => Carbon::today()->addDays(5)->toDateString(),
            'note' => 'Sensitivity on the lower left when drinking anything cold.',
            'status' => 'pending',
        ]);
        AppointmentRequest::create([
            'patient_id' => $patients[3]->id, 'doctor_id' => $staff['rana']->id,
            'branch_id' => $staff['rana']->branch_id,
            'preferred_date' => Carbon::today()->addDays(8)->toDateString(),
            'note' => 'Would like to discuss whitening top-up. Afternoons only please.',
            'status' => 'pending',
        ]);
        AppointmentRequest::create([
            'patient_id' => $patients[1]->id, 'doctor_id' => $staff['karim']->id,
            'branch_id' => $staff['karim']->branch_id,
            'preferred_date' => Carbon::today()->addDays(2)->toDateString(),
            'note' => 'Chipped a front tooth.',
            'status' => 'pending',
        ]);

        // --- Lab work in various stages ---
        $labs = [
            [2, 'karim', 'Zirconia crown — 46', 3, 'in_progress', 180],
            [5, 'karim', 'Porcelain bridge — 24 to 26', 9, 'received', 420],
            [7, 'nour', 'Upper partial denture', 1, 'ready', 350],
            [10, 'rana', 'Night guard', -4, 'delivered', 90],
        ];
        foreach ($labs as [$p, $docKey, $type, $due, $status, $cost]) {
            LabCase::create([
                'patient_id' => $patients[$p]->id,
                'doctor_id' => $staff[$docKey]->id,
                'type' => $type,
                'due_date' => Carbon::today()->addDays($due)->toDateString(),
                'status' => $status,
                'cost' => $cost,
            ]);
        }

        // --- Imaging, taken at the studio ---
        $media = [
            [2, 'xray', 'periapical', 'radiographs/46-periapical.jpg', -14],
            [2, 'xray', 'periapical', 'radiographs/46-post-op.jpg', -7],
            [5, 'scan', 'intraoral', 'scans/upper-arch.stl', -7],
            [3, 'photo', 'before', 'photos/whitening-before.jpg', -12],
            [3, 'photo', 'after', 'photos/whitening-after.jpg', -12],
            [4, 'xray', 'panoramic', 'radiographs/opg-full.jpg', -9],
        ];
        foreach ($media as [$p, $type, $category, $file, $days]) {
            Media::create([
                'patient_id' => $patients[$p]->id,
                'branch_id' => $branches['studio']->id,
                'type' => $type,
                'category' => $category,
                'file_url' => $file,
                'taken_at' => Carbon::today()->addDays($days)->setTime(10, 30),
            ]);
        }
    }

    /** first.last@clinic.local, with any title stripped off the front. */
    private function emailFor(string $name): string
    {
        $clean = str_replace(['Dr. ', 'Dr '], '', $name);
        $slug = strtolower(str_replace([' ', "'"], ['.', ''], $clean));

        return $slug.'@clinic.local';
    }
}
