<?php

namespace App\Application\Organizations\UseCases;

use App\Application\Organizations\Commands\UpdateOrganizationCommand;
use App\Domain\Organizations\Entities\Organization;
use App\Domain\Organizations\Repositories\OrganizationRepositoryInterface;

class UpdateOrganizationUseCase
{
    public function __construct(
        private OrganizationRepositoryInterface $organizations,
    ) {}

    public function handle(UpdateOrganizationCommand $command): Organization
    {
        $organization = $this->organizations->update($command->organization, [
            'name' => $command->name,
            'legal_name' => $command->legalName,
            'inn' => $command->inn,
            'organization_type_id' => $command->organizationTypeId,
            'industry_id' => $command->industryId,
        ]);

        return $organization->load(['type', 'industry']);
    }
}
