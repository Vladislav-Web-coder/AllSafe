<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Identity\Entities\RefreshToken;
use App\Domain\Identity\Entities\User;
use App\Domain\Identity\Repositories\RefreshTokenRepositoryInterface;
use Illuminate\Support\Facades\Hash;

class EloquentRefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    public function createForUser(
        User $user,
        string $plainToken,
        ?string $ipAddress,
        ?string $userAgent,
        ?string $deviceName,
    ): RefreshToken {
        return RefreshToken::query()->create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainToken),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'device_name' => $deviceName,
            'expires_at' => now()->addDays(30),
            'revoked_at' => null,
        ]);
    }

    public function findByPlainToken(string $plainToken): ?RefreshToken
    {
        $hash = hash('sha256', $plainToken);

        return RefreshToken::query()
            ->where('token_hash', $hash)
            ->where('revoked_at', null)
            ->where('expires_at', '>', now())
            ->first();
    }

    public function deleteByTokenableId(int $tokenId): void
    {
        RefreshToken::query()
            ->where('tokenable_id', $tokenId)
            ->delete();
    }

    public function deleteExpired(): int
    {
        return RefreshToken::query()
            ->where(function ($query) {
                $query->where('expires_at', '<', now())
                    ->orWhereNotNull('revoked_at');
            })
            ->delete();
    }
}
