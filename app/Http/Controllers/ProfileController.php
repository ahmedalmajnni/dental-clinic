<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Everyone's own account page. Whoever is signed in edits their own details
 * here — nothing in this controller can reach another person's record, so it is
 * safe for all three roles to share.
 *
 * Job title, branch and active status stay read-only: those belong to the admin
 * (see AccountController and EmployeeController), not to the person themselves.
 */
class ProfileController extends Controller
{
    public function edit()
    {
        $account = Auth::user();

        return view('profile.edit', [
            'account' => $account,
            'owner' => $account->employee ?? $account->patient,
        ]);
    }

    /** Name, phone and — for patients — date of birth, plus the login email. */
    public function update(Request $request)
    {
        $account = Auth::user();
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
        // The login email must stay unique across every account but this one.
        if ($email !== '' && Account::where('email', $email)->where('id', '!=', $account->id)->exists()) {
            $errors[] = 'That email is already in use.';
        }
        if (! $owner) {
            $errors[] = 'This account has no profile attached — ask an administrator.';
        }

        if ($errors) {
            return back()->withInput()->with('flash', ['type' => 'error', 'message' => implode(' ', $errors)]);
        }

        DB::transaction(function () use ($request, $account, $owner, $name, $email) {
            $fields = ['name' => $name, 'phone' => $request->input('phone') ?: null];

            // Only patients carry these two; an employee record has neither.
            if ($account->role === 'patient') {
                $fields['dob'] = $request->input('dob') ?: null;
                $fields['email'] = $email;
            }

            $owner->update($fields);
            $account->update(['email' => $email]);
        });

        return redirect()->route('profile.edit')->with('flash', ['type' => 'success', 'message' => 'Your details were saved.']);
    }

    /** Password change, gated on knowing the current one. */
    public function password(Request $request)
    {
        $account = Auth::user();

        $current = $request->input('current_password', '');
        $new = $request->input('password', '');
        $confirm = $request->input('password_confirmation', '');

        $errors = [];
        if (! Hash::check($current, $account->password_hash)) {
            $errors[] = 'Your current password is not correct.';
        }
        if (strlen($new) < 6) {
            $errors[] = 'The new password must be at least 6 characters.';
        }
        if ($new !== $confirm) {
            $errors[] = 'The two new passwords do not match.';
        }

        if ($errors) {
            return back()->with('flash', ['type' => 'error', 'message' => implode(' ', $errors)]);
        }

        $account->update(['password_hash' => Hash::make($new)]);

        return redirect()->route('profile.edit')->with('flash', ['type' => 'success', 'message' => 'Your password was changed.']);
    }
}
