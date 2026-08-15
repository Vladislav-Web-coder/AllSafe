<?php

namespace App\Domain\Organizations\Entities;

use App\Domain\Identity\Entities\User;
use Domain\Organizations\Entities\OrganizationUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    protected $connection = 'pgsql_identity';

    protected $table = 'organizations';

    protected $fillable = [
        'name',
        'legal_name',
        'inn',
        'organization_type_id',
        'industry_id',
        'status',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(OrganizationType::class, 'organization_type_id');
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class, 'industry_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationUser::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->using(OrganizationUser::class)
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }
}
