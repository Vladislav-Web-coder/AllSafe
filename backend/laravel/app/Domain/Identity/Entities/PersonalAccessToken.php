<?php

namespace App\Domain\Identity\Entities;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    protected $connection = 'pgsql_identity';
}
