<?php

namespace App\Application\Organizations\UseCases;

use App\Application\Organizations\Commands\InviteMemberCommand;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use App\Domain\Identity\Services\EmailService;
use App\Domain\Organizations\Entities\OrganizationInvitation;
use App\Domain\Organizations\Repositories\OrganizationInvitationRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InviteMemberUseCase
{
    public function __construct(
        private OrganizationInvitationRepositoryInterface $invitations,
        private UserRepositoryInterface                   $users,
        private EmailService                              $emailService,
    )
    {
    }

    public function handle(InviteMemberCommand $command): OrganizationInvitation
    {
        // Проверяем, существует ли пользователь с таким email
        $existingUser = $this->users->findByEmail($command->email);

        // Проверяем, не является ли пользователь уже участником
        if ($existingUser) {
            $isMember = DB::connection('pgsql_identity')
                ->table('organization_user')
                ->where('organization_id', $command->organizationId)
                ->where('user_id', $existingUser->id)
                ->exists();

            if ($isMember) {
                throw ValidationException::withMessages([
                    'email' => ['Этот пользователь уже является участником организации.'],
                ]);
            }
        }

        // Проверяем, нет ли уже активного приглашения
        $existingInvitation = $this->invitations->findByEmailAndOrganization(
            $command->email,
            $command->organizationId
        );

        if ($existingInvitation && !$existingInvitation->isExpired()) {
            throw ValidationException::withMessages([
                'email' => ['Приглашение для этого пользователя уже отправлено.'],
            ]);
        }

        // Создаём приглашение
        $token = Str::random(64);

        $invitation = DB::connection('pgsql_identity')->transaction(function () use ($command, $token) {
            return $this->invitations->create([
                'organization_id' => $command->organizationId,
                'email' => $command->email,
                'role' => $command->role,
                'token' => hash('sha256', $token),
                'invited_by' => $command->invitedBy,
                'status' => 'pending',
                'expires_at' => now()->addDays(7),
            ]);
        });

        // Отправляем письмо
        $organization = \App\Domain\Organizations\Entities\Organization::find($command->organizationId);
        $inviter = \App\Domain\Identity\Entities\User::find($command->invitedBy);

        $this->emailService->sendInvitation(
            email: $command->email,
            organizationName: $organization->name,
            inviterName: $inviter->name,
            role: $command->role,
            token: $token,
        );

        return $invitation;
    }
}
