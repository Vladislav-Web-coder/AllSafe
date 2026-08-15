<?php

namespace App\Application\Organizations\UseCases;

use App\Application\Organizations\Commands\RemoveOrganizationMemberCommand;
use App\Domain\Organizations\Enums\OrganizationRole;
use App\Domain\Organizations\Repositories\OrganizationMemberRepositoryInterface;
use Illuminate\Validation\ValidationException;

class RemoveOrganizationMemberUseCase
{
    public function __construct(
        private OrganizationMemberRepositoryInterface $members,
    ) {}

    public function handle(RemoveOrganizationMemberCommand $command): void
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
                'user_id' => ['Нельзя удалить владельца организации.'],
            ]);
        }

        $this->members->delete($membership);
    }
}
