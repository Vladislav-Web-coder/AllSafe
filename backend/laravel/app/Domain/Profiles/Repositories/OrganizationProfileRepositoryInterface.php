<?php

namespace App\Domain\Profiles\Repositories;

use App\Domain\Profiles\Entities\OrganizationProfile;

interface OrganizationProfileRepositoryInterface
{
    public function findByOrganizationId(int $organizationId): ?OrganizationProfile;

    public function createOrUpdate(int $organizationId, array $data): OrganizationProfile;
}
