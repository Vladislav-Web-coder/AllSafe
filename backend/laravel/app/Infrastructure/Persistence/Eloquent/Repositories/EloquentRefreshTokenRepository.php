<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Identity\Entities\RefreshToken;
use App\Domain\Identity\Entities\User;
use App\Domain\Identity\Repositories\RefreshTokenRepositoryInterface;

class EloquentRefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    public function createForUser(
        User $user,
        string $plainToken,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        string $deviceName = 'api',
    ): RefreshToken {
        return RefreshToken::query()->create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDays(14),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'device_name' => $deviceName,
        ]);
    }

    public function findActiveByPlainToken(string $plainToken): ?RefreshToken
    {
        $hash = hash('sha256', $plainToken);

        return RefreshToken::query()
            ->where('token_hash', $hash)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    public function revoke(RefreshToken $refreshToken): void
    {
        $refreshToken->forceFill([
            'revoked_at' => now(),
            'last_used_at' => now(),
        ])->save();
    }
}
