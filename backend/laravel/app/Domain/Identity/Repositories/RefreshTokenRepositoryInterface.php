<?php

namespace App\Domain\Identity\Repositories;

use App\Domain\Identity\Entities\RefreshToken;
use App\Domain\Identity\Entities\User;

interface RefreshTokenRepositoryInterface
{
    public function createForUser(
        User $user,
        string $plainToken,
        ?string $ipAddress,
        ?string $userAgent,
        ?string $deviceName,
    ): RefreshToken;

    public function findByPlainToken(string $plainToken): ?RefreshToken;

    public function deleteByTokenableId(int $tokenId): void;

    public function deleteExpired(): int;
}
