<?php

namespace App\Application\Auth\UseCases;

use App\Application\Auth\Commands\LoginCommand;
use App\Application\Auth\DTO\AuthTokens;
use App\Domain\Identity\Repositories\RefreshTokenRepositoryInterface;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LoginUseCase
{
    public function __construct(
        private UserRepositoryInterface $users,
        private RefreshTokenRepositoryInterface $refreshTokens,
    ) {}

    public function handle(LoginCommand $command): AuthTokens
    {
        $user = $this->users->findByEmail($command->email);

        if (! $user || ! Hash::check($command->password, $user->password)) {
            throw new AuthenticationException('Неверный email или пароль.');
        }

        if (! $user->is_active) {
            throw new AuthenticationException('Аккаунт отключён.');
        }

        return DB::connection('pgsql_identity')->transaction(function () use ($command, $user) {
            $this->users->updateLastLogin($user, $command->ipAddress);

            $accessToken = $user->createToken('api', ['base']);

            $refreshPlain = Str::random(64);

            $this->refreshTokens->createForUser(
                user: $user,
                plainToken: $refreshPlain,
                ipAddress: $command->ipAddress,
                userAgent: $command->userAgent,
                deviceName: 'api',
            );

            return new AuthTokens(
                accessToken: $accessToken->plainTextToken,
                refreshToken: $refreshPlain,
                expiresIn: 1800,
            );
        });
    }
}
