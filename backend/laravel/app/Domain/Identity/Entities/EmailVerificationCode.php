<?php

namespace App\Domain\Identity\Entities;

use Illuminate\Database\Eloquent\Model;

class EmailVerificationCode extends Model
{
    protected $connection = 'pgsql_identity';

    protected $table = 'email_verification_codes';

    protected $fillable = [
        'user_id',
        'email',
        'purpose',
        'code',
        'used',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'used' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return ! $this->used && ! $this->isExpired();
    }

    public function markAsUsed(): void
    {
        $this->update(['used' => true]);
    }

    public function scopeActive($query)
    {
        return $query->where('used', false)
            ->where('expires_at', '>', now());
    }
}
