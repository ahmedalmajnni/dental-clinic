<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    /** An admin may create any staff role, including another admin. */
    private const STAFF_JOBS = ['doctor', 'reception', 'lab_tech', 'admin'];

    /**
     * Staff and patient logins are different enough that one shared table was
     * showing half-empty columns to both. They now sit behind two tabs, each
     * listing only the fields that mean something for that kind of account.
     */
    public function index(Request $request)
    {
        $type = $request->query('type') === 'patient' ? 'patient' : 'staff';

        $accounts = Account::query()
            ->when($type === 'staff',
                fn ($q) => $q->whereIn('role', ['admin', 'employee'])->with('employee.branch'),
                fn ($q) => $q->where('role', 'patient')->with('patient'),
            )
            ->orderByDesc('created_at')->get();

        return view('accounts.index', [
            'accounts' => $accounts,
            'type' => $type,
            'staffCount' => Account::whereIn('role', ['admin', 'employee'])->count(),
            'patientCount' => Account::where('role', 'patient')->count(),
        ]);
    }

    // Admin creates a PATIENT account (name + login). Employees are no longer
    // added here — they self-register and are approved via the staff requests.
    public function create()
    {
        return view('accounts.form', ['form' => []]);
    }

    public function store(Request $request)
    {
        $name = trim($request->input('pat_name', ''));
        $email = strtolower(trim($request->input('email', '')));
        $password = $request->input('password', '');

        $errors = [];
        if ($name === '') {
            $errors[] = 'Patient name is required.';
        }
        if ($email === '') {
            $errors[] = 'Login email is required.';
        }
        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }
        if ($email !== '' && Account::where('email', $email)->exists()) {
            $errors[] = 'That email is already in use.';
        }

        if ($errors) {
            return back()->withInput()->with('flash', ['type' => 'error', 'message' => implode(' ', $errors)]);
        }

        DB::transaction(function () use ($request, $name, $email, $password) {
            $patient = Patient::create([
                'name' => $name,
                'dob' => $request->input('dob') ?: null,
                'phone' => $request->input('pat_phone') ?: null,
                'email' => $email,
            ]);
            Account::create([
                'email' => $email, 'password_hash' => Hash::make($password),
                'role' => 'patient', 'patient_id' => $patient->id,
            ]);
        });

        return redirect()->route('accounts.index')->with('flash', ['type' => 'success', 'message' => 'Patient account created.']);
    }

    // ---- Staff approval queue ----

    // Pending staff sign-ups: employee accounts that are not active yet.
    public function requests()
    {
        $pending = Account::with('employee.branch')
            ->where('role', 'employee')->where('is_active', false)
            ->orderByDesc('created_at')->get();

        return view('accounts.requests', compact('pending'));
    }

    // ---- Admin creating a staff login directly ----

    /**
     * Hiring someone should not mean waiting for them to self-register. An admin
     * creates the employee and their login here, already active — unlike
     * StaffRegistrationController, which creates an inactive account pending
     * approval. Admins may also grant the 'admin' job title, which nobody can
     * give themselves through the public form.
     */
    public function createStaff()
    {
        return view('accounts.staff_form', [
            'branches' => Branch::orderBy('name')->get(),
            'jobTitles' => self::STAFF_JOBS,
            'form' => [],
        ]);
    }

    public function storeStaff(Request $request)
    {
        $name = trim($request->input('name', ''));
        $email = strtolower(trim($request->input('email', '')));
        $password = $request->input('password', '');
        $branchId = $request->input('branch_id');
        $jobTitle = $request->input('job_title');

        $errors = [];
        if ($name === '') {
            $errors[] = 'Name is required.';
        }
        if ($email === '') {
            $errors[] = 'Login email is required.';
        }
        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }
        if (! $branchId || ! Branch::whereKey($branchId)->exists()) {
            $errors[] = 'Choose a branch.';
        }
        if (! in_array($jobTitle, self::STAFF_JOBS, true)) {
            $errors[] = 'Choose a valid job title.';
        }
        if ($email !== '' && Account::where('email', $email)->exists()) {
            $errors[] = 'That email is already in use.';
        }

        if ($errors) {
            return back()->withInput()->with('flash', ['type' => 'error', 'message' => implode(' ', $errors)]);
        }

        DB::transaction(function () use ($name, $email, $password, $branchId, $jobTitle) {
            $employee = Employee::create([
                'name' => $name,
                'branch_id' => $branchId,
                'job_title' => $jobTitle,
                'phone' => request('phone') ?: null,
            ]);
            Account::create([
                'email' => $email,
                'password_hash' => Hash::make($password),
                // An 'admin' employee gets the admin role; everyone else is staff.
                'role' => $jobTitle === 'admin' ? 'admin' : 'employee',
                'employee_id' => $employee->id,
                'is_active' => true,
            ]);
        });

        return redirect()->route('accounts.index', ['type' => 'staff'])
            ->with('flash', ['type' => 'success', 'message' => 'Staff account created — they can log in straight away.']);
    }

    // ---- Admin editing somebody else's account ----

    /**
     * An admin may correct anyone's contact details and reset their password.
     * Job title and branch stay with EmployeeController, and active status with
     * toggle(), so each of those rules lives in exactly one place.
     */
    public function edit(Account $account)
    {
        $account->load(['employee.branch', 'patient']);

        return view('accounts.edit', [
            'account' => $account,
            'owner' => $account->employee ?? $account->patient,
        ]);
    }

    public function update(Request $request, Account $account)
    {
        $owner = $account->employee ?? $account->patient;
        $name = trim($request->input('name', ''));
        $email = strtolower(trim($request->input('email', '')));

        $errors = [];
        if ($name === '') {
            $errors[] = 'Name is required.';
        }
        if ($email === '') {
            $errors[] = 'Email is required.';
        }
        if ($email !== '' && Account::where('email', $email)->where('id', '!=', $account->id)->exists()) {
            $errors[] = 'That email is already in use.';
        }
        if (! $owner) {
            $errors[] = 'This account has no employee or patient record attached.';
        }

        if ($errors) {
            return back()->withInput()->with('flash', ['type' => 'error', 'message' => implode(' ', $errors)]);
        }

        DB::transaction(function () use ($request, $account, $owner, $name, $email) {
            $fields = ['name' => $name, 'phone' => $request->input('phone') ?: null];
            if ($account->role === 'patient') {
                $fields['dob'] = $request->input('dob') ?: null;
                $fields['email'] = $email;
            }
            $owner->update($fields);
            $account->update(['email' => $email]);
        });

        return redirect()->route('accounts.index', ['type' => $account->role === 'patient' ? 'patient' : 'staff'])
            ->with('flash', ['type' => 'success', 'message' => 'Account updated.']);
    }

    /**
     * Reset someone's password. An admin cannot know the current one, so this
     * sets a new password outright rather than asking for the old.
     */
    public function resetPassword(Request $request, Account $account)
    {
        $new = $request->input('password', '');
        $confirm = $request->input('password_confirmation', '');

        $errors = [];
        if (strlen($new) < 6) {
            $errors[] = 'The new password must be at least 6 characters.';
        }
        if ($new !== $confirm) {
            $errors[] = 'The two passwords do not match.';
        }

        if ($errors) {
            return back()->with('flash', ['type' => 'error', 'message' => implode(' ', $errors)]);
        }

        $account->update(['password_hash' => Hash::make($new)]);

        return redirect()->route('accounts.edit', $account)
            ->with('flash', ['type' => 'success', 'message' => 'Password reset — tell them their new password.']);
    }

    public function approve(Account $account)
    {
        $account->update(['is_active' => true]);

        return redirect()->route('accounts.requests')->with('flash', ['type' => 'success', 'message' => 'Staff account approved — they can now log in.']);
    }

    public function reject(Account $account)
    {
        // Remove the request entirely — deleting the employee cascades the login.
        if ($account->employee) {
            $account->employee->delete();
        } else {
            $account->delete();
        }

        return redirect()->route('accounts.requests')->with('flash', ['type' => 'success', 'message' => 'Request rejected and removed.']);
    }

    public function toggle(Account $account)
    {
        if ($account->id === Auth::id()) {
            return redirect()->route('accounts.index')->with('flash', ['type' => 'error', 'message' => 'You cannot deactivate your own account.']);
        }
        // Guard: don't deactivate the last active admin (would leave no admins).
        if ($account->is_active && $account->role === 'admin'
            && Account::where('role', 'admin')->where('is_active', true)->where('id', '!=', $account->id)->count() === 0) {
            return redirect()->route('accounts.index')->with('flash', ['type' => 'error', 'message' => 'Cannot deactivate the last active admin. Create or activate another admin first.']);
        }
        $account->is_active = ! $account->is_active;
        $account->save();

        return redirect()->route('accounts.index')->with('flash', ['type' => 'success', 'message' => 'Account status changed.']);
    }
}
