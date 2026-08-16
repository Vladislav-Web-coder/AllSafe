<?php

namespace App\Domain\Requirements\Services;

use App\Domain\Profiles\Entities\OrganizationProfile;
use App\Domain\Requirements\Entities\DocumentRequirementRule;
use App\Domain\Requirements\Repositories\DocumentRequirementRuleRepositoryInterface;
use Illuminate\Support\Collection;

class RequiredDocumentsCalculator
{
    public function __construct(
        private DocumentRequirementRuleRepositoryInterface $rules,
    ) {}

    /**
     * Подбирает обязательные документы на основе профиля организации.
     *
     * @return Collection<DocumentRequirementRule>
     */
    public function calculate(OrganizationProfile $profile): Collection
    {
        $allRules = $this->rules->getActive();

        return $allRules->filter(function (DocumentRequirementRule $rule) use ($profile) {
            return $this->matchCondition($rule, $profile);
        });
    }

    /**
     * Проверяет, соответствует ли профиль условию правила.
     */
    private function matchCondition(DocumentRequirementRule $rule, OrganizationProfile $profile): bool
    {
        $condition = $rule->condition_json ?? [];

        if (empty($condition)) {
            return true;
        }

        foreach ($condition as $key => $expectedValue) {
            $actualValue = $this->getProfileValue($profile, $key);

            if ($actualValue !== $expectedValue) {
                return false;
            }
        }

        return true;
    }

    /**
     * Получает значение из профиля по ключу.
     */
    private function getProfileValue(OrganizationProfile $profile, string $key): mixed
    {
        return match ($key) {
            'processes_personal_data' => $profile->processes_personal_data,
            'has_website' => $profile->has_website,
            'has_gis' => $profile->has_gis,
            'has_kii' => $profile->has_kii,
            'has_asutp' => $profile->has_asutp,
            'uses_cloud' => $profile->uses_cloud,
            'has_contractors' => $profile->has_contractors,
            'has_cross_border_transfer' => $profile->has_cross_border_transfer,
            'has_special_categories' => $profile->hasSpecialCategories(),
            'has_large_subjects_count' => $profile->hasLargeSubjectsCount(),
            default => $profile->extra_attributes[$key] ?? null,
        };
    }
}
