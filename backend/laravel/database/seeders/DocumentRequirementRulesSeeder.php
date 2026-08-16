<?php

namespace Database\Seeders;

use App\Domain\Documents\Entities\DocumentType;
use App\Domain\Requirements\Entities\DocumentRequirementRule;
use Illuminate\Database\Seeder;

class DocumentRequirementRulesSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            // Политика ПДн — обязательна если обрабатывают ПДн
            [
                'code' => 'pd_policy_required',
                'document_type_code' => 'pd_policy',
                'condition_json' => [
                    'processes_personal_data' => true,
                ],
                'priority' => 1,
                'obligation_level' => 'required',
                'legal_basis_json' => ['152-ФЗ, ст. 18.1'],
                'description' => 'Политика обработки персональных данных обязательна для всех операторов ПДн.',
            ],

            // Приказ об ответственном за ПДн
            [
                'code' => 'pd_responsible_order_required',
                'document_type_code' => 'pd_responsible_order',
                'condition_json' => [
                    'processes_personal_data' => true,
                ],
                'priority' => 2,
                'obligation_level' => 'required',
                'legal_basis_json' => ['152-ФЗ, ст. 22.1'],
                'description' => 'Приказ о назначении ответственного за организацию обработки ПДн.',
            ],

            // Перечень ИСПДн
            [
                'code' => 'ispdn_list_required',
                'document_type_code' => 'ispdn_list',
                'condition_json' => [
                    'processes_personal_data' => true,
                ],
                'priority' => 3,
                'obligation_level' => 'required',
                'legal_basis_json' => ['152-ФЗ', 'ПП РФ № 1119'],
                'description' => 'Перечень информационных систем персональных данных.',
            ],

            // Согласие на обработку ПДн
            [
                'code' => 'pd_consent_required',
                'document_type_code' => 'pd_consent',
                'condition_json' => [
                    'processes_personal_data' => true,
                ],
                'priority' => 4,
                'obligation_level' => 'required',
                'legal_basis_json' => ['152-ФЗ, ст. 9'],
                'description' => 'Согласие субъекта на обработку персональных данных.',
            ],

            // Политика конфиденциальности сайта
            [
                'code' => 'site_privacy_policy_required',
                'document_type_code' => 'privacy_policy_site',
                'condition_json' => [
                    'has_website' => true,
                    'processes_personal_data' => true,
                ],
                'priority' => 5,
                'obligation_level' => 'required',
                'legal_basis_json' => ['152-ФЗ, ст. 18.1'],
                'description' => 'Политика конфиденциальности для сайта, собирающего ПДн.',
            ],

            // Регламент управления доступом
            [
                'code' => 'access_regulation_required',
                'document_type_code' => 'access_regulation',
                'condition_json' => [
                    'processes_personal_data' => true,
                ],
                'priority' => 10,
                'obligation_level' => 'required',
                'legal_basis_json' => ['Приказ ФСТЭК № 21'],
                'description' => 'Регламент управления доступом к информационным системам.',
            ],

            // Регламент реагирования на инциденты
            [
                'code' => 'incident_response_required',
                'document_type_code' => 'incident_response_regulation',
                'condition_json' => [
                    'processes_personal_data' => true,
                ],
                'priority' => 11,
                'obligation_level' => 'required',
                'legal_basis_json' => ['152-ФЗ, ст. 21'],
                'description' => 'Регламент реагирования на инциденты ИБ.',
            ],

            // Регламент резервного копирования
            [
                'code' => 'backup_regulation_required',
                'document_type_code' => 'backup_regulation',
                'condition_json' => [
                    'processes_personal_data' => true,
                ],
                'priority' => 12,
                'obligation_level' => 'recommended',
                'legal_basis_json' => ['Приказ ФСТЭК № 21'],
                'description' => 'Регламент резервного копирования данных.',
            ],

            // Модель угроз
            [
                'code' => 'threat_model_required',
                'document_type_code' => 'threat_model',
                'condition_json' => [
                    'processes_personal_data' => true,
                ],
                'priority' => 6,
                'obligation_level' => 'required',
                'legal_basis_json' => ['Методика ФСТЭК'],
                'description' => 'Модель угроз безопасности информации.',
            ],

            // Перечень объектов КИИ
            [
                'code' => 'kii_object_list_required',
                'document_type_code' => 'kii_object_list',
                'condition_json' => [
                    'has_kii' => true,
                ],
                'priority' => 20,
                'obligation_level' => 'required',
                'legal_basis_json' => ['187-ФЗ, ст. 7'],
                'description' => 'Перечень объектов критической информационной инфраструктуры.',
            ],

            // Акт классификации ГИС
            [
                'code' => 'gis_classification_required',
                'document_type_code' => 'gis_classification_act',
                'condition_json' => [
                    'has_gis' => true,
                ],
                'priority' => 30,
                'obligation_level' => 'required',
                'legal_basis_json' => ['Приказ ФСТЭК № 17'],
                'description' => 'Акт классификации государственной информационной системы.',
            ],
        ];

        foreach ($rules as $rule) {
            $documentType = DocumentType::query()
                ->where('code', $rule['document_type_code'])
                ->first();

            if (! $documentType) {
                $this->command->warn(
                    "Тип документа '{$rule['document_type_code']}' не найден. Правило '{$rule['code']}' пропущено."
                );
                continue;
            }

            DocumentRequirementRule::query()->updateOrCreate(
                ['code' => $rule['code']],
                [
                    'document_type_id' => $documentType->id,
                    'condition_json' => $rule['condition_json'],
                    'priority' => $rule['priority'],
                    'obligation_level' => $rule['obligation_level'],
                    'legal_basis_json' => $rule['legal_basis_json'],
                    'description' => $rule['description'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Правила обязательности документов загружены.');
    }
}
