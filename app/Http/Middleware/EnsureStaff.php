<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        /**
         * @var \App\Models\User $user
         */
        $user = $request->user();

        if ($request->expectsJson() || $request->is('api/*')) {
            if (! $user) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            if (! $user->isAdminStaff() || ! $user->is_active) {
                return response()->json(['message' => 'Staff access required.'], 403);
            }

            return $next($request);
        }

        if (! $user) {
            return redirect()->guest(route('staff.login'));
        }

        if ($user->isCitizen() && $user->is_active) {
            return redirect()
                ->route('citizen.dashboard')
                ->with('status', 'That page is for staff. You are logged in as a citizen.');
        }

        if (! $user->isAdminStaff() || ! $user->is_active) {
            abort(403, 'Staff access required.');
        }

        return $next($request);
    }
}
