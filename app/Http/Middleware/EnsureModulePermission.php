<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModulePermission
{
    public function handle(Request $request, Closure $next, string $module, string $action = 'view'): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'You do not have permission for this action.');
        }

        $modules = explode('|', $module);
        $actions = explode('|', $action);

        $allowed = false;
        foreach ($modules as $m) {
            foreach ($actions as $a) {
                if ($user->hasModuleAction(trim($m), trim($a))) {
                    $allowed = true;
                    break 2;
                }
            }
        }

        if (! $allowed) {
            abort(403, 'You do not have permission for this action.');
        }

        return $next($request);
    }
}
