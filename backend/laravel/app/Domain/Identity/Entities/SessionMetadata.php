<?php

namespace App\Domain\Identity\Entities;

use Illuminate\Database\Eloquent\Model;

class SessionMetadata extends Model
{
    protected $connection = 'pgsql_identity';

    protected $table = 'session_metadata';

    protected $fillable = [
        'user_id',
        'token_id',
        'device_name',
        'ip_address',
        'user_agent',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'last_activity_at' => 'datetime',
        ];
    }
}
