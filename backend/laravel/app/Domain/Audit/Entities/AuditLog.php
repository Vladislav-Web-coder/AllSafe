<?php

namespace App\Domain\Audit\Entities;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $connection = 'pgsql_audit';

    protected $table = 'audit_logs';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_email',
        'organization_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'request_id',
        'result',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
