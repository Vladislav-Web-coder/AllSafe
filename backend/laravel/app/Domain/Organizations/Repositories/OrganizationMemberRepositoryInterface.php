<?php

namespace App\Domain\Organizations\Repositories;

use App\Domain\Organizations\Entities\Organization;
use App\Domain\Organizations\Enums\OrganizationRole;
use Domain\Organizations\Entities\OrganizationUser;
use Illuminate\Database\Eloquent\Collection;

interface OrganizationMemberRepositoryInterface
{
    public function listForOrganization(Organization $organization): Collection;

    public function findByOrganizationAndUser(
        Organization $organization,
        int $userId
    ): ?OrganizationUser;

    public function create(
        Organization $organization,
        int $userId,
        OrganizationRole $role,
    ): OrganizationUser;

    public function updateRole(
        OrganizationUser $membership,
        OrganizationRole $role
    ): OrganizationUser;

    public function delete(OrganizationUser $membership): void;
}

