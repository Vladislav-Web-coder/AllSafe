<?php

namespace App\Application\Organizations\Commands;

class CreateOrganizationCommand
{
    public function __construct(
        public readonly int $ownerId,
        public readonly string $name,
        public readonly ?string $legalName = null,
        public readonly ?string $inn = null,
        public readonly ?int $organizationTypeId = null,
        public readonly ?int $industryId = null,
    ) {}
}
