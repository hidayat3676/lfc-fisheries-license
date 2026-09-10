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

        if (! $user || ! $user->hasModuleAction($module, $action)) {
            abort(403, 'You do not have permission for this action.');
        }

        return $next($request);
    }
}
