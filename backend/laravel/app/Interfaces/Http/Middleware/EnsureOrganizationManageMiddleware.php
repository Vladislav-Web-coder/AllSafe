<?php

namespace Interfaces\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureOrganizationManageMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $role = $request->attributes->get('currentOrganizationRole');

        if(!$role || !$role->canManageOrganization()) {
            abort(403, 'Недостаточно прав для управления организацией.');
        }

        return $next($request);
    }
}
