<?php

namespace App\Domain\Organizations\Entities;

use Illuminate\Database\Eloquent\Model;

class Industry extends Model
{
    protected $connection = 'pgsql_identity';

    protected $table = 'industries';

    protected $fillable = [
        'code',
        'name',
        'description',
        'kii_relevant',
        'is_active',
        'applicability_json',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'kii_relevant' => 'boolean',
            'is_active' => 'boolean',
            'applicability_json' => 'array',
        ];
    }
}
