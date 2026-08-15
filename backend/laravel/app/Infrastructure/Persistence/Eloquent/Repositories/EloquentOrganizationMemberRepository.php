<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Organizations\Entities\Organization;

use App\Domain\Organizations\Enums\OrganizationRole;
use App\Domain\Organizations\Repositories\OrganizationMemberRepositoryInterface;
use Domain\Organizations\Entities\OrganizationUser;
use Illuminate\Database\Eloquent\Collection;

class EloquentOrganizationMemberRepository implements OrganizationMemberRepositoryInterface
{
    public function listForOrganization(Organization $organization): Collection
    {
        return OrganizationUser::query()
            ->where('organization_id', $organization->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findByOrganizationAndUser(
        Organization $organization,
        int $userId,
    ): ?OrganizationUser {
        return OrganizationUser::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $userId)
            ->first();
    }

    public function create(
        Organization $organization,
        int $userId,
        OrganizationRole $role,
    ): OrganizationUser {
        return OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $userId,
            'role' => $role,
            'joined_at' => now(),
        ]);
    }

    public function updateRole(
        OrganizationUser $membership,
        OrganizationRole $role,
    ): OrganizationUser {
        $membership->update([
            'role' => $role,
        ]);

        return $membership->refresh();
    }

    public function delete(OrganizationUser $membership): void
    {
        $membership->delete();
    }
}
