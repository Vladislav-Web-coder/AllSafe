<?php

namespace App\Domain\Identity\Repositories;

use App\Domain\Identity\Entities\RefreshToken;
use App\Domain\Identity\Entities\User;
interface RefreshTokenRepositoryInterface
{
    public function createForUser(
        User $user,
        string $plainToken,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        string $deviceName = 'api',
    ): RefreshToken;

    public function findActiveByPlainToken(string $plainToken): ?RefreshToken;

    public function revoke(RefreshToken $refreshToken): void;
}
