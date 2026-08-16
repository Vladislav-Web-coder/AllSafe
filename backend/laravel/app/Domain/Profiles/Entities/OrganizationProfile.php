<?php

namespace App\Domain\Profiles\Entities;

use App\Domain\Organizations\Entities\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationProfile extends Model
{
    protected $connection = 'pgsql_identity';

    protected $table = 'organization_profiles';

    protected $fillable = [
        'organization_id',
        'processes_personal_data',
        'has_website',
        'has_gis',
        'has_kii',
        'has_asutp',
        'uses_cloud',
        'has_contractors',
        'has_cross_border_transfer',
        'data_categories',
        'special_data_categories',
        'subjects_count',
        'protection_level',
        'extra_attributes',
    ];

    protected function casts(): array
    {
        return [
            'processes_personal_data' => 'boolean',
            'has_website' => 'boolean',
            'has_gis' => 'boolean',
            'has_kii' => 'boolean',
            'has_asutp' => 'boolean',
            'uses_cloud' => 'boolean',
            'has_contractors' => 'boolean',
            'has_cross_border_transfer' => 'boolean',
            'data_categories' => 'array',
            'special_data_categories' => 'array',
            'extra_attributes' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function hasSpecialCategories(): bool
    {
        return ! empty($this->special_data_categories);
    }

    public function hasLargeSubjectsCount(): bool
    {
        return ($this->subjects_count ?? 0) > 100000;
    }
}
