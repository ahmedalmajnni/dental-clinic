<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Employee;
use App\Models\Specialty;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Creates the first admin login (with a default specialty + admin employee, since
 * every account must attach to an employee). Idempotent: run it as often as you
 * like. The PHP equivalent of the Node app's scripts/seed-admin.js.
 *
 * Run with:  php artisan db:seed --class=AdminSeeder
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = strtolower(env('ADMIN_EMAIL', 'admin@clinic.local'));
        $password = env('ADMIN_PASSWORD', 'admin123');

        if (Account::where('email', $email)->exists()) {
            $this->command->info("Admin {$email} already exists — nothing to do.");

            return;
        }

        DB::transaction(function () use ($email, $password) {
            $specialty = Specialty::first() ?? Specialty::create(['name' => 'General dentistry']);
            $employee = Employee::create([
                'name' => env('ADMIN_NAME', 'Clinic Admin'),
                'job_title' => 'admin',
                'specialty' => $specialty->name,
            ]);
            Account::create([
                'email' => $email,
                'password_hash' => Hash::make($password),
                'role' => 'admin',
                'employee_id' => $employee->id,
            ]);
        });

        $this->command->info("Admin login created:  {$email}  /  {$password}");
    }
}
