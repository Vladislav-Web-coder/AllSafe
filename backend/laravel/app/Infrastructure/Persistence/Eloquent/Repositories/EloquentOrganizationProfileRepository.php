<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Profiles\Entities\OrganizationProfile;
use App\Domain\Profiles\Repositories\OrganizationProfileRepositoryInterface;

class EloquentOrganizationProfileRepository implements OrganizationProfileRepositoryInterface
{
    public function findByOrganizationId(int $organizationId): ?OrganizationProfile
    {
        return OrganizationProfile::query()
            ->where('organization_id', $organizationId)
            ->first();
    }

    public function createOrUpdate(int $organizationId, array $data): OrganizationProfile
    {
        return OrganizationProfile::query()->updateOrCreate(
            ['organization_id' => $organizationId],
            $data
        );
    }
}
