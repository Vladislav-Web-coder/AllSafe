<?php

namespace App\Application\Auth\Commands;

use App\Domain\Identity\Entities\RefreshToken;
use App\Domain\Identity\Entities\User;

class LogoutCommand
{
    public function __construct(
        public readonly User $user,
        public readonly ?string $refreshToken = null
    )
    {}
}
