<?php

namespace App\Domain\Organizations\Entities;

use Illuminate\Database\Eloquent\Model;

class OrganizationType extends Model
{
    protected $connection = 'pgsql_identity';

    protected $table = 'organization_types';

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
        'applicability_json',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'applicability_json' => 'array',
        ];
    }
}
