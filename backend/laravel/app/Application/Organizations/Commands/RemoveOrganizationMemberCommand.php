<?php

namespace App\Application\Organizations\Commands;

use App\Domain\Organizations\Entities\Organization;

class RemoveOrganizationMemberCommand
{
    public function __construct(
        public readonly Organization $organization,
        public readonly int $userId,
    ) {}
}
