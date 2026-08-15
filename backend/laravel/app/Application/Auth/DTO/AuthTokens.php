<?php

namespace App\Application\Auth\DTO;

final class AuthTokens
{
    public function __construct(
        public readonly string $accessToken,
        public readonly string $refreshToken,
        public readonly int $expiresIn,
        public readonly string $tokenType = 'Bearer',
    ) {}
}
