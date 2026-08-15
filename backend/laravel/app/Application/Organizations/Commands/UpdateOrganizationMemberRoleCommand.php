<?php

namespace App\Application\Organizations\Commands;

use App\Domain\Organizations\Entities\Organization;
use App\Domain\Organizations\Enums\OrganizationRole;

class UpdateOrganizationMemberRoleCommand
{
    public function __construct(
        public readonly Organization $organization,
        public readonly int $userId,
        public readonly OrganizationRole $role,
    ) {}
}
