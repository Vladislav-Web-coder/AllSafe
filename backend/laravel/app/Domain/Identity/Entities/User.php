<?php

namespace App\Domain\Identity\Entities;

use App\Domain\Organizations\Entities\Organization;
use App\Domain\Organizations\Enums\OrganizationRole;
use Domain\Organizations\Entities\OrganizationUser;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $connection = 'pgsql_identity';

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
            ->using(OrganizationUser::class)
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }
    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationUser::class);
    }
    public function roleInOrganization(int $organizationId): ?OrganizationRole
    {
        $memberships = $this->memberships
            ->where('organization_id', $organizationId)
            ->first();

        return $memberships?->role;
    }

    public function belongsToOrganization(int $organizationId): bool
    {
        return $this->memberships
            ->where('organization_id', $organizationId)
            ->isNotEmpty();
    }
}
