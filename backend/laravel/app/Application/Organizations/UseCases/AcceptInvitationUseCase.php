<?php

namespace App\Application\Organizations\UseCases;

use App\Domain\Identity\Entities\User;
use App\Domain\Organizations\Repositories\OrganizationInvitationRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcceptInvitationUseCase
{
    public function __construct(
        private OrganizationInvitationRepositoryInterface $invitations,
    ) {}

    public function handle(string $token, User $user): void
    {
        $invitation = $this->invitations->findByToken(hash('sha256', $token));

        if (! $invitation) {
            throw ValidationException::withMessages([
                'token' => ['Приглашение не найдено.'],
            ]);
        }

        if ($invitation->status !== 'pending') {
            throw ValidationException::withMessages([
                'token' => ['Приглашение уже было использовано.'],
            ]);
        }

        if ($invitation->isExpired()) {
            throw ValidationException::withMessages([
                'token' => ['Приглашение истекло.'],
            ]);
        }

        // Проверяем, что email приглашения совпадает с email пользователя
        if ($invitation->email !== $user->email) {
            throw ValidationException::withMessages([
                'token' => ['Приглашение выдано для другого email.'],
            ]);
        }

        // Проверяем, что пользователь ещё не участник
        $isMember = DB::connection('pgsql_identity')
            ->table('organization_user')
            ->where('organization_id', $invitation->organization_id)
            ->where('user_id', $user->id)
            ->exists();

        if ($isMember) {
            throw ValidationException::withMessages([
                'token' => ['Вы уже являетесь участником этой организации.'],
            ]);
        }

        DB::connection('pgsql_identity')->transaction(function () use ($invitation, $user) {
            // Добавляем пользователя в организацию
            DB::connection('pgsql_identity')
                ->table('organization_user')
                ->insert([
                    'organization_id' => $invitation->organization_id,
                    'user_id' => $user->id,
                    'role' => $invitation->role,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            // Обновляем приглашение
            $this->invitations->update($invitation, [
                'status' => 'accepted',
                'accepted_at' => now(),
            ]);
        });
    }
}
