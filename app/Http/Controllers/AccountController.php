<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    public function index()
    {
        $accounts = Account::with(['employee', 'patient'])->orderByDesc('created_at')->get();

        return view('accounts.index', compact('accounts'));
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
