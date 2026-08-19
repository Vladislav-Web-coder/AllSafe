<?php

namespace App\Domain\Notifications\Entities;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $connection = 'pgsql_core';

    protected $table = 'notifications';

    protected $fillable = [
        'organization_id',
        'user_id',
        'type',
        'title',
        'message',
        'link_type',
        'link_id',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markAsRead(): void
    {
        if (! $this->isRead()) {
            $this->update(['read_at' => now()]);
        }
    }
}
