<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Employee;
use App\Models\Specialty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Public self-registration for staff. Anyone can submit a request, but the
 * account is created INACTIVE — a manager must approve it (activate it) before
 * the person can log in. Staff cannot pick the "admin" job title themselves.
 */
class StaffRegistrationController extends Controller
{
    private const SELF_JOBS = ['doctor', 'reception', 'lab_tech'];

    public function create()
    {
        if (Auth::check()) {
            return redirect('/dashboard');
        }

        return view('auth.staff_register', [
            'specialties' => Specialty::orderBy('name')->get(),
            'jobTitles' => self::SELF_JOBS,
            'form' => [],
        ]);
    }

    public function store(Request $request)
    {
        $name = trim($request->input('name', ''));
        $email = strtolower(trim($request->input('email', '')));
        $password = $request->input('password', '');
        $specialty = trim($request->input('specialty', '')) ?: null;
        $jobTitle = $request->input('job_title');

        $errors = [];
        if ($name === '') {
            $errors[] = 'Name is required.';
        }
        if ($email === '') {
            $errors[] = 'Email is required.';
        }
        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }
        if (! in_array($jobTitle, self::SELF_JOBS, true)) {
            $errors[] = 'Choose a valid job title.';
        }
        if ($specialty !== null && ! Specialty::where('name', $specialty)->exists()) {
            $errors[] = 'Choose a valid specialty.';
        }
        if ($email !== '' && Account::where('email', $email)->exists()) {
            $errors[] = 'That email is already registered.';
        }

        if ($errors) {
            return back()->withInput()->with('flash', ['type' => 'error', 'message' => implode(' ', $errors)]);
        }

        // Create the employee record and an INACTIVE login (pending approval).
        DB::transaction(function () use ($name, $email, $password, $specialty, $jobTitle) {
            $employee = Employee::create([
            'name' => $name, 'specialty' => $specialty, 'job_title' => $jobTitle,
            ]);
            Account::create([
                'email' => $email,
                'password_hash' => Hash::make($password),
                'role' => 'employee',
                'employee_id' => $employee->id,
                'is_active' => false,
            ]);
        });

        return redirect('/login')->with('flash', ['type' => 'success', 'message' => 'Request submitted. A manager will review it — you can log in once your account is approved.']);
    }
}
