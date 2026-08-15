<?php

namespace App\Application\Organizations\UseCases;

use App\Application\Organizations\Commands\AddOrganizationMemberCommand;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use Domain\Organizations\Entities\OrganizationUser;
use App\Domain\Organizations\Repositories\OrganizationMemberRepositoryInterface;
use Illuminate\Validation\ValidationException;

class AddOrganizationMemberUseCase
{
    public function __construct(
        private UserRepositoryInterface $users,
        private OrganizationMemberRepositoryInterface $members,
    ) {}

    public function handle(AddOrganizationMemberCommand $command): OrganizationUser
    {
        $user = $this->users->findByEmail($command->email);

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['Пользователь с таким email не найден.'],
            ]);
        }

        $existingMembership = $this->members->findByOrganizationAndUser(
            organization: $command->organization,
            userId: $user->id,
        );

        if ($existingMembership) {
            throw ValidationException::withMessages([
                'email' => ['Пользователь уже является участником организации.'],
            ]);
        }

        return $this->members->create(
            organization: $command->organization,
            userId: $user->id,
            role: $command->role,
        );
    }
}
