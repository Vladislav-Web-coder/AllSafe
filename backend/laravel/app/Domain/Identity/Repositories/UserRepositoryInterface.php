<?php

namespace App\Domain\Identity\Repositories;

use App\Domain\Identity\Entities\User;
interface UserRepositoryInterface
{
    public function findById(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function updateLastLogin(User $user, ?string $ip): void;
}
