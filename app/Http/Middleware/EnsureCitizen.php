<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCitizen
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if ($user->isAdminStaff() && $user->is_active) {
            $application = $request->route('application');
            if ($application && $user->hasModuleAction('applications', 'view')) {
                return redirect()
                    ->route('admin.applications.show', $application)
                    ->with('status', 'Opened in staff review (citizen portal requires a citizen login).');
            }

            return redirect()
                ->route('admin.dashboard')
                ->with('status', 'That page is for citizens. You are logged in as staff.');
        }

        if (! $user->isCitizen() || ! $user->is_active) {
            abort(403, 'Citizen access required.');
        }

        return $next($request);
    }
}
