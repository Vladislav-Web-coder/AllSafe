<?php

namespace App\Application\Auth\UseCases;

use App\Application\Auth\Commands\LogoutCommand;
use App\Domain\Identity\Repositories\RefreshTokenRepositoryInterface;

class LogoutUseCase
{
    public function __construct(
        private RefreshTokenRepositoryInterface $refreshTokens,
    ) {}

    public function handle(LogoutCommand $command): void
    {
        $command->user->currentAccessToken()?->delete();

        if ($command->refreshToken) {
            $refreshToken = $this->refreshTokens->findActiveByPlainToken(
                $command->refreshToken
            );

            if ($refreshToken) {
                $this->refreshTokens->revoke($refreshToken);
            }
        }
    }
}
