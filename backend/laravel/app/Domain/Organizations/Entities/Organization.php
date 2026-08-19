<?php

namespace App\Domain\Organizations\Entities;

use App\Domain\Documents\Entities\Document;
use App\Domain\Identity\Entities\User;
use App\Domain\Profiles\Entities\OrganizationProfile;
use App\Domain\Tasks\Entities\Task;
use Domain\Organizations\Entities\OrganizationUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use SoftDeletes;
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
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_user')
            ->using(OrganizationUser::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(\App\Domain\Documents\Entities\Document::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(\App\Domain\Tasks\Entities\Task::class);
    }

    public function profile(): HasMany
    {
        return $this->hasMany(\App\Domain\Profiles\Entities\OrganizationProfile::class);
    }

    public function isOwner(User $user): bool
    {
        $member = $this->members()->where('user_id', $user->id)->first();

        return $member && $member->pivot->role === 'owner';
    }

    public function isMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }
}
