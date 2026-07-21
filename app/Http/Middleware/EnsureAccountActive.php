<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Re-checks account.is_active on EVERY authenticated request, not just at
 * login. Without this, deactivating (or rejecting) an account while its
 * owner is already logged in has no effect until they happen to log out —
 * their session cookie keeps working and keeps renewing on activity, so a
 * fired/suspended staff member (or a declined self-registration) would keep
 * full access to patient data indefinitely. This forces an immediate logout
 * the moment such a session makes its next request.
 */
class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')->with('flash', [
                'type' => 'error',
                'message' => 'Your account has been deactivated. Please contact a manager.',
            ]);
        }

        return $next($request);
    }
}
