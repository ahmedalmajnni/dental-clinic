<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return Auth::check() ? redirect('/dashboard') : view('auth.login');
    }

    public function login(Request $request)
    {
        $email = strtolower(trim($request->input('email', '')));
        $password = $request->input('password', '');

        $account = Account::where('email', $email)->first();

        // One generic message whether the email is unknown or the password wrong.
        if (! $account || ! Hash::check($password, $account->password_hash)) {
            return back()->with('flash', ['type' => 'error', 'message' => 'Invalid email or password.']);
        }

        // Correct credentials, but the account is not active yet (e.g. a staff
        // member awaiting manager approval, or a deactivated account).
        if (! $account->is_active) {
            return back()->with('flash', ['type' => 'error', 'message' => 'Your account is not active yet. A manager must approve it before you can log in.']);
        }

        $account->last_login = now();
        $account->save();

        Auth::login($account);
        $request->session()->regenerate();

        return redirect('/dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function showSignup()
    {
        return Auth::check() ? redirect('/dashboard') : view('auth.signup', ['form' => []]);
    }

    public function signup(Request $request)
    {
        $name = trim($request->input('name', ''));
        $email = strtolower(trim($request->input('email', '')));
        $password = $request->input('password', '');

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
        if ($email !== '' && Account::where('email', $email)->exists()) {
            $errors[] = 'That email is already registered.';
        }

        if ($errors) {
            return back()->withInput()->with('flash', ['type' => 'error', 'message' => implode(' ', $errors)]);
        }

        DB::transaction(function () use ($name, $email, $password, $request) {
            $patient = Patient::create([
                'name' => $name,
                'email' => $email,
                'phone' => $request->input('phone') ?: null,
                'dob' => $request->input('dob') ?: null,
            ]);
            Account::create([
                'email' => $email,
                'password_hash' => Hash::make($password),
                'role' => 'patient',
                'patient_id' => $patient->id,
            ]);
        });

        return redirect('/login')->with('flash', ['type' => 'success', 'message' => 'Account created — please log in.']);
    }
}
