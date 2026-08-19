<?php

namespace App\Domain\Identity\Entities;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
class RefreshToken extends Model
{
    use HasUlids;

    protected $connection = 'pgsql_identity';
    protected $table = 'auth_refresh_tokens';

    protected $fillable = [
        'user_id',
        'token_hash',
        'tokenable_id',
        'expires_at',
        'revoked_at',
        'last_used_at',
        'ip_address',
        'user_agent',
        'device_name',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
