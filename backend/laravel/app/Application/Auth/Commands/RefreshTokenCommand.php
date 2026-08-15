<?php

namespace App\Application\Auth\Commands;

class RefreshTokenCommand
{
    public function __construct(
        public readonly string $refreshToken,
        public readonly ?string $ipAddress = null,
        public readonly ?string $userAgent = null,
    ) {}
}
