<?php

namespace App\Application\Organizations\Commands;

use App\Domain\Organizations\Entities\Organization;
use App\Domain\Organizations\Enums\OrganizationRole;

class AddOrganizationMemberCommand
{
    public function __construct(
        public readonly Organization $organization,
        public readonly string $email,
        public readonly OrganizationRole $role,
        public readonly int $invitedByUserId,
    ) {}
}
