<?php

namespace App\Application\Organizations\UseCases;

use App\Application\Organizations\Commands\UpdateOrganizationMemberRoleCommand;
use Domain\Organizations\Entities\OrganizationUser;
use App\Domain\Organizations\Enums\OrganizationRole;
use App\Domain\Organizations\Repositories\OrganizationMemberRepositoryInterface;
use Illuminate\Validation\ValidationException;

class UpdateOrganizationMemberRoleUseCase
{
    public function __construct(
        private OrganizationMemberRepositoryInterface $members,
    ) {}

    public function handle(UpdateOrganizationMemberRoleCommand $command): OrganizationUser
    {
        $membership = $this->members->findByOrganizationAndUser(
            organization: $command->organization,
            userId: $command->userId,
        );

        if (! $membership) {
            throw ValidationException::withMessages([
                'user_id' => ['Пользователь не является участником организации.'],
            ]);
        }

        if ($membership->role === OrganizationRole::Owner) {
            throw ValidationException::withMessages([
                'role' => ['Нельзя изменить роль владельца организации.'],
            ]);
        }

        return $this->members->updateRole($membership, $command->role);
    }
}
