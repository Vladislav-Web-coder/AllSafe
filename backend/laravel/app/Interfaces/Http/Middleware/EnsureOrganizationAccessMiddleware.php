<?php

namespace Interfaces\Http\Middleware;

use App\Domain\Organizations\Entities\Organization;
use Closure;
use Domain\Organizations\Entities\OrganizationUser;
use Illuminate\Http\Request;

class EnsureOrganizationAccessMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        $organizationId = (int) $request->route('organizationId');

        $organization = Organization::query()->findOrFail($organizationId);

        $membership = OrganizationUser::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$membership) {
            abort(403, 'Доступ к организации запрещён.');
        }

        $request->attributes->set('currentOrganization', $organization);
        $request->attributes->set('currentOrganizationRole', $membership->role);
        $request->attributes->set('currentOrganizationMembership', $membership);

        return $next($request);
    }
}
