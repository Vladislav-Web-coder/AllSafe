<?php

namespace App\Application\Organizations\UseCases;

use App\Application\Organizations\Commands\CreateOrganizationCommand;
use App\Domain\Organizations\Entities\Organization;
use App\Domain\Organizations\Enums\OrganizationRole;
use App\Domain\Organizations\Repositories\OrganizationMemberRepositoryInterface;
use App\Domain\Organizations\Repositories\OrganizationRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CreateOrganizationUseCase
{
    public function __construct(
        private OrganizationRepositoryInterface $organizations,
        private OrganizationMemberRepositoryInterface $members,
    ) {}

    public function handle(CreateOrganizationCommand $command): Organization
    {
        return DB::connection('pgsql_identity')->transaction(function () use ($command) {
            $organization = $this->organizations->create([
                'name' => $command->name,
                'legal_name' => $command->legalName,
                'inn' => $command->inn,
                'organization_type_id' => $command->organizationTypeId,
                'industry_id' => $command->industryId,
                'status' => 'active',
            ]);

            $this->members->create(
                organization: $organization,
                userId: $command->ownerId,
                role: OrganizationRole::Owner,
            );

            return $organization->load(['type', 'industry']);
        });
    }
}
