<?php

namespace App\Application\Organizations\Commands;

class InviteMemberCommand
{
    public function __construct(
        public readonly int $organizationId,
        public readonly string $email,
        public readonly string $role,
        public readonly int $invitedBy,
    ) {}
}
