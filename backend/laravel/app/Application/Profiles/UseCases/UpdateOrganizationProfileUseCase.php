<?php

namespace App\Application\Profiles\UseCases;

use App\Application\Profiles\Commands\UpdateOrganizationProfileCommand;
use App\Domain\Profiles\Entities\OrganizationProfile;
use App\Domain\Profiles\Repositories\OrganizationProfileRepositoryInterface;
use Illuminate\Support\Facades\DB;

class UpdateOrganizationProfileUseCase
{
    public function __construct(
        private OrganizationProfileRepositoryInterface $profiles,
    ) {}

    public function handle(UpdateOrganizationProfileCommand $command): OrganizationProfile
    {
        return DB::connection('pgsql_identity')->transaction(function () use ($command) {
            return $this->profiles->createOrUpdate($command->organizationId, [
                'processes_personal_data' => $command->processesPersonalData,
                'has_website' => $command->hasWebsite,
                'has_gis' => $command->hasGis,
                'has_kii' => $command->hasKii,
                'has_asutp' => $command->hasAsutp,
                'uses_cloud' => $command->usesCloud,
                'has_contractors' => $command->hasContractors,
                'has_cross_border_transfer' => $command->hasCrossBorderTransfer,
                'data_categories' => $command->dataCategories,
                'special_data_categories' => $command->specialDataCategories,
                'subjects_count' => $command->subjectsCount,
                'protection_level' => $command->protectionLevel,
                'extra_attributes' => $command->extraAttributes,
            ]);
        });
    }
}
