<?php

namespace App\Application\Auth\UseCases;

use App\Application\Auth\Commands\RefreshTokenCommand;
use App\Application\Auth\DTO\AuthTokens;
use App\Domain\Identity\Repositories\RefreshTokenRepositoryInterface;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RefreshTokenUseCase
{
    public function __construct(
        private RefreshTokenRepositoryInterface $refreshTokens,
    ) {}

    public function handle(RefreshTokenCommand $command): AuthTokens
    {
        $refreshToken = $this->refreshTokens->findActiveByPlainToken(
            $command->refreshToken
        );

        if (! $refreshToken) {
            throw new AuthenticationException('Refresh token недействителен.');
        }

        return DB::connection('pgsql_identity')->transaction(function () use ($command, $refreshToken) {
            $this->refreshTokens->revoke($refreshToken);

            $user = $refreshToken->user;

            $accessToken = $user->createToken('api', ['base']);

            $newRefreshPlain = Str::random(64);

            $this->refreshTokens->createForUser(
                user: $user,
                plainToken: $newRefreshPlain,
                ipAddress: $command->ipAddress,
                userAgent: $command->userAgent,
                deviceName: 'api',
            );

            return new AuthTokens(
                accessToken: $accessToken->plainTextToken,
                refreshToken: $newRefreshPlain,
                expiresIn: 1800,
            );
        });
    }
}
