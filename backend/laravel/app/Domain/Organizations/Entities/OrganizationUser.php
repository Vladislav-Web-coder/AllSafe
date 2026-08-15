<?php

namespace Domain\Organizations\Entities;

use App\Domain\Identity\Entities\User;
use App\Domain\Organizations\Entities\Organization;
use App\Domain\Organizations\Enums\OrganizationRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationUser extends Model
{
    protected $connection = 'pgsql_identity';

    protected $table = 'organization_user';

    protected $fillable = [
        'organization_id',
        'user_id',
        'role',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'role' => OrganizationRole::class,
            'joined_at' => 'datetime',
        ];
    }
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
