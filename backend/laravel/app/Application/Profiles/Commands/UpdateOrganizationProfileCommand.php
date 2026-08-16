<?php

namespace App\Application\Profiles\Commands;

class UpdateOrganizationProfileCommand
{
    public function __construct(
        public readonly int $organizationId,
        public readonly bool $processesPersonalData = false,
        public readonly bool $hasWebsite = false,
        public readonly bool $hasGis = false,
        public readonly bool $hasKii = false,
        public readonly bool $hasAsutp = false,
        public readonly bool $usesCloud = false,
        public readonly bool $hasContractors = false,
        public readonly bool $hasCrossBorderTransfer = false,
        public readonly ?array $dataCategories = null,
        public readonly ?array $specialDataCategories = null,
        public readonly ?int $subjectsCount = null,
        public readonly ?string $protectionLevel = null,
        public readonly ?array $extraAttributes = null,
    ) {}
}
