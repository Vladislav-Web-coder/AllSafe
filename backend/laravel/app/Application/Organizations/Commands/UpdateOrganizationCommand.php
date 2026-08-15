<?php

namespace App\Application\Organizations\Commands;

use App\Domain\Organizations\Entities\Organization;

class UpdateOrganizationCommand
{
    public function __construct(
        public readonly Organization $organization,
        public readonly string $name,
        public readonly ?string $legalName = null,
        public readonly ?string $inn = null,
        public readonly ?int $organizationTypeId = null,
        public readonly ?int $industryId = null,
    ) {}
}
