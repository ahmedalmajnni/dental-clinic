<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route guard by role — the Laravel equivalent of the Node app's requireRole().
 * Usage in routes:  ->middleware('role:admin')  or  ->middleware('role:admin,employee')
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::user();
        if (! $user) {
            return redirect('/login');
        }
        if (in_array($user->role, $roles, true)) {
            return $next($request);
        }
        abort(403, 'This page is only for: '.implode(', ', $roles).'. You are logged in as "'.$user->role.'".');
    }
}
