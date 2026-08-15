<?php

namespace Database\Seeders;

use App\Domain\Organizations\Entities\Industry;
use App\Domain\Organizations\Entities\OrganizationType;
use Illuminate\Database\Seeder;

class OrganizationDictionarySeeder extends Seeder
{
    public function run(): void
    {
        $organizationTypes = [
            [
                'code' => 'government',
                'name' => 'Государственный орган',
                'description' => 'Орган государственной власти или государственный орган',
                'sort_order' => 1,
            ],
            [
                'code' => 'municipal',
                'name' => 'Муниципальный орган',
                'description' => 'Орган местного самоуправления или муниципальное учреждение',
                'sort_order' => 2,
            ],
            [
                'code' => 'budget_institution',
                'name' => 'Бюджетное учреждение',
                'description' => 'Бюджетное, казённое или автономное учреждение',
                'sort_order' => 3,
            ],
            [
                'code' => 'commercial',
                'name' => 'Коммерческая организация',
                'description' => 'Коммерческая юридическая лицо',
                'sort_order' => 4,
            ],
            [
                'code' => 'non_profit',
                'name' => 'Некоммерческая организация',
                'description' => 'НКО, фонд, ассоциация, общественная организация',
                'sort_order' => 5,
            ],
            [
                'code' => 'credit_organization',
                'name' => 'Кредитная организация',
                'description' => 'Банк или иная кредитная организация',
                'sort_order' => 6,
            ],
            [
                'code' => 'telecom_operator',
                'name' => 'Оператор связи',
                'description' => 'Организация, оказывающая услуги связи',
                'sort_order' => 7,
            ],
            [
                'code' => 'individual_entrepreneur',
                'name' => 'Индивидуальный предприниматель',
                'description' => 'ИП',
                'sort_order' => 8,
            ],
        ];

        foreach ($organizationTypes as $type) {
            OrganizationType::query()->updateOrCreate(
                ['code' => $type['code']],
                [
                    'name' => $type['name'],
                    'description' => $type['description'] ?? null,
                    'is_active' => true,
                    'sort_order' => $type['sort_order'] ?? 0,
                ]
            );
        }

        $industries = [
            [
                'code' => 'public_administration',
                'name' => 'Государственное управление',
                'kii_relevant' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'healthcare',
                'name' => 'Здравоохранение',
                'kii_relevant' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'education',
                'name' => 'Образование',
                'kii_relevant' => false,
                'sort_order' => 3,
            ],
            [
                'code' => 'finance',
                'name' => 'Финансы',
                'kii_relevant' => true,
                'sort_order' => 4,
            ],
            [
                'code' => 'banking',
                'name' => 'Банковская сфера',
                'kii_relevant' => true,
                'sort_order' => 5,
            ],
            [
                'code' => 'telecom',
                'name' => 'Связь и телекоммуникации',
                'kii_relevant' => true,
                'sort_order' => 6,
            ],
            [
                'code' => 'energy',
                'name' => 'Энергетика',
                'kii_relevant' => true,
                'sort_order' => 7,
            ],
            [
                'code' => 'transport',
                'name' => 'Транспорт',
                'kii_relevant' => true,
                'sort_order' => 8,
            ],
            [
                'code' => 'manufacturing',
                'name' => 'Производство',
                'kii_relevant' => true,
                'sort_order' => 9,
            ],
            [
                'code' => 'it',
                'name' => 'Информационные технологии',
                'kii_relevant' => false,
                'sort_order' => 10,
            ],
            [
                'code' => 'retail',
                'name' => 'Торговля',
                'kii_relevant' => false,
                'sort_order' => 11,
            ],
            [
                'code' => 'media',
                'name' => 'СМИ и массовые коммуникации',
                'kii_relevant' => false,
                'sort_order' => 12,
            ],
            [
                'code' => 'science',
                'name' => 'Наука',
                'kii_relevant' => true,
                'sort_order' => 13,
            ],
            [
                'code' => 'other',
                'name' => 'Другое',
                'kii_relevant' => false,
                'sort_order' => 99,
            ],
        ];

        foreach ($industries as $industry) {
            Industry::query()->updateOrCreate(
                ['code' => $industry['code']],
                [
                    'name' => $industry['name'],
                    'description' => $industry['description'] ?? null,
                    'kii_relevant' => $industry['kii_relevant'] ?? false,
                    'is_active' => true,
                    'sort_order' => $industry['sort_order'] ?? 0,
                ]
            );
        }
    }
}
