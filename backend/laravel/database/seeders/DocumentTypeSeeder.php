<?php

namespace Database\Seeders;

use App\Domain\Documents\Entities\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'code' => 'pd_policy',
                'name' => 'Политика обработки персональных данных',
                'category' => 'personal_data',
                'description' => 'Основной документ, описывающий обработку и защиту персональных данных',
                'can_be_generated' => true,
                'required_sections_json' => [
                    'Цели обработки',
                    'Категории субъектов',
                    'Категории данных',
                    'Сроки обработки',
                    'Порядок уничтожения',
                    'Ответственное лицо',
                ],
                'legal_basis_json' => [
                    '152-ФЗ',
                ],
                'sort_order' => 1,
            ],
            [
                'code' => 'privacy_policy_site',
                'name' => 'Политика конфиденциальности сайта',
                'category' => 'personal_data',
                'description' => 'Публичная политика для сайта',
                'can_be_generated' => true,
                'required_sections_json' => [
                    'Собираемые данные',
                    'Цели сбора',
                    'Cookie',
                    'Права субъекта',
                ],
                'legal_basis_json' => [
                    '152-ФЗ',
                ],
                'sort_order' => 2,
            ],
            [
                'code' => 'pd_responsible_order',
                'name' => 'Приказ о назначении ответственного за ПДн',
                'category' => 'personal_data',
                'description' => 'Приказ о назначении ответственного за организацию обработки ПДн',
                'can_be_generated' => true,
                'required_sections_json' => [
                    'Основание',
                    'Ответственное лицо',
                    'Обязанности',
                ],
                'legal_basis_json' => [
                    '152-ФЗ',
                ],
                'sort_order' => 3,
            ],
            [
                'code' => 'ispdn_list',
                'name' => 'Перечень ИСПДн',
                'category' => 'personal_data',
                'description' => 'Перечень информационных систем персональных данных',
                'can_be_generated' => true,
                'required_sections_json' => [
                    'Перечень систем',
                    'Категории данных',
                    'Уровни защищённости',
                ],
                'legal_basis_json' => [
                    '152-ФЗ',
                    'ПП РФ № 1119',
                ],
                'sort_order' => 4,
            ],
            [
                'code' => 'pd_consent',
                'name' => 'Согласие на обработку ПДн',
                'category' => 'personal_data',
                'description' => 'Форма согласия субъекта персональных данных',
                'can_be_generated' => true,
                'required_sections_json' => [
                    'Субъект',
                    'Оператор',
                    'Цель обработки',
                    'Перечень данных',
                    'Срок действия',
                    'Порядок отзыва',
                ],
                'legal_basis_json' => [
                    '152-ФЗ',
                ],
                'sort_order' => 5,
            ],
            [
                'code' => 'access_regulation',
                'name' => 'Регламент управления доступом',
                'category' => 'general_security',
                'description' => 'Порядок предоставления, изменения и отзыва доступа',
                'can_be_generated' => true,
                'required_sections_json' => [
                    'Выдача доступа',
                    'Изменение доступа',
                    'Отзыв доступа',
                    'Привилегированные учётные записи',
                ],
                'legal_basis_json' => [],
                'sort_order' => 6,
            ],
            [
                'code' => 'incident_response_regulation',
                'name' => 'Регламент реагирования на инциденты ИБ',
                'category' => 'general_security',
                'description' => 'Порядок действий при инцидентах информационной безопасности',
                'can_be_generated' => true,
                'required_sections_json' => [
                    'Классификация инцидентов',
                    'Порядок реагирования',
                    'Эскалация',
                    'Отчётность',
                ],
                'legal_basis_json' => [],
                'sort_order' => 7,
            ],
            [
                'code' => 'backup_regulation',
                'name' => 'Регламент резервного копирования',
                'category' => 'general_security',
                'description' => 'Порядок резервного копирования и восстановления данных',
                'can_be_generated' => true,
                'required_sections_json' => [
                    'Объекты копирования',
                    'Периодичность',
                    'Сроки хранения',
                    'Проверка восстановления',
                ],
                'legal_basis_json' => [],
                'sort_order' => 8,
            ],
            [
                'code' => 'threat_model',
                'name' => 'Модель угроз безопасности информации',
                'category' => 'security_model',
                'description' => 'Документ с описанием угроз, источников угроз и мер защиты',
                'can_be_generated' => true,
                'required_sections_json' => [
                    'Активы',
                    'Источники угроз',
                    'Угрозы',
                    'Уязвимости',
                    'Меры защиты',
                ],
                'legal_basis_json' => [
                    'Методика ФСТЭК',
                ],
                'sort_order' => 9,
            ],
            [
                'code' => 'kii_object_list',
                'name' => 'Перечень объектов КИИ',
                'category' => 'kii',
                'description' => 'Перечень объектов критической информационной инфраструктуры',
                'can_be_generated' => true,
                'required_sections_json' => [
                    'Объекты',
                    'Основания включения',
                    'Категорирование',
                ],
                'legal_basis_json' => [
                    '187-ФЗ',
                ],
                'sort_order' => 10,
            ],
            [
                'code' => 'gis_classification_act',
                'name' => 'Акт классификации ГИС',
                'category' => 'gis',
                'description' => 'Акт определения класса защищённости государственной информационной системы',
                'can_be_generated' => true,
                'required_sections_json' => [
                    'Наименование ГИС',
                    'Класс защищённости',
                    'Основание',
                ],
                'legal_basis_json' => [
                    'Приказ ФСТЭК № 17',
                ],
                'sort_order' => 11,
            ],
        ];

        foreach ($types as $type) {
            DocumentType::query()->updateOrCreate(
                ['code' => $type['code']],
                [
                    'name' => $type['name'],
                    'category' => $type['category'],
                    'description' => $type['description'] ?? null,
                    'is_active' => true,
                    'can_be_generated' => $type['can_be_generated'] ?? false,
                    'required_sections_json' => $type['required_sections_json'] ?? [],
                    'legal_basis_json' => $type['legal_basis_json'] ?? [],
                    'sort_order' => $type['sort_order'] ?? 0,
                ]
            );
        }
    }
}
