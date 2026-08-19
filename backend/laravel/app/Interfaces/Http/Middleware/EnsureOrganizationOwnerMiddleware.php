<?php

namespace App\Interfaces\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationOwnerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $organization = $request->attributes->get('currentOrganization');

        if (! $organization) {
            abort(404, 'Организация не найдена.');
        }

        $user = $request->user();

        $membership = DB::connection('pgsql_identity')
            ->table('organization_user')
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $membership || $membership->role !== 'owner') {
            abort(403, 'Доступно только владельцу организации.');
        }

        return $next($request);
    }
}
