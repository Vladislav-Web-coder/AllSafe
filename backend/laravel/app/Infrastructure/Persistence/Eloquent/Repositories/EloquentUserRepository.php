<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Identity\Entities\User;
use App\Domain\Identity\Repositories\UserRepositoryInterface;

class EloquentUserRepository implements UserRepositoryInterface
{

    public function findById(int $id): ?User
    {
        return User::query()->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()
            ->where("email", $email)
            ->first();
    }

    public function updateLastLogin(User $user, ?string $ip): void
    {
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $ip
        ])->save();
    }
}
