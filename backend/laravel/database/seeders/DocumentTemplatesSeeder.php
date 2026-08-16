<?php

namespace Database\Seeders;

use App\Domain\Documents\Entities\DocumentType;
use App\Domain\Generation\Entities\DocumentTemplate;
use Illuminate\Database\Seeder;

class DocumentTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'code' => 'pd_policy_template',
                'document_type_code' => 'pd_policy',
                'name' => 'Политика обработки персональных данных',
                'description' => 'Шаблон политики обработки ПДн для оператора персональных данных',
                'generation_prompt' => 'Сгенерируй политику обработки персональных данных для организации. Документ должен соответствовать требованиям 152-ФЗ. Включи все обязательные разделы. Используй формальный юридический язык. Указывай конкретные статьи 152-ФЗ, где применимо. Учитывай категорию обрабатываемых данных и уровень защищённости из профиля организации.',
                'required_sections_json' => [
                    'Общие положения',
                    'Основные понятия',
                    'Цели обработки персональных данных',
                    'Категории субъектов персональных данных',
                    'Категории обрабатываемых персональных данных',
                    'Порядок и условия обработки персональных данных',
                    'Меры по обеспечению безопасности персональных данных',
                    'Права субъектов персональных данных',
                    'Порядок уничтожения персональных данных',
                    'Ответственное лицо за организацию обработки персональных данных',
                    'Заключительные положения',
                ],
                'template_variables_json' => [
                    'organization_name',
                    'legal_name',
                    'inn',
                    'protection_level',
                    'data_categories',
                ],
            ],
            [
                'code' => 'pd_responsible_order_template',
                'document_type_code' => 'pd_responsible_order',
                'name' => 'Приказ о назначении ответственного за ПДн',
                'description' => 'Шаблон приказа о назначении ответственного за организацию обработки ПДн',
                'generation_prompt' => 'Сгенерируй приказ о назначении ответственного за организацию обработки персональных данных. Документ должен соответствовать требованиям 152-ФЗ, ст. 22.1. Включи преамбулу, основную часть с назначением ответственного лица, его обязанности, и заключительные положения. Оставь место для ФИО и должности ответственного лица.',
                'required_sections_json' => [
                    'Преамбула',
                    'Назначение ответственного лица',
                    'Обязанности ответственного лица',
                    'Контроль исполнения',
                ],
                'template_variables_json' => [
                    'organization_name',
                    'legal_name',
                ],
            ],
            [
                'code' => 'ispdn_list_template',
                'document_type_code' => 'ispdn_list',
                'name' => 'Перечень ИСПДн',
                'description' => 'Шаблон перечня информационных систем персональных данных',
                'generation_prompt' => 'Сгенерируй перечень информационных систем персональных данных (ИСПДн). Документ должен включать таблицу с перечнем ИС, категориями обрабатываемых данных, уровнями защищённости и ответственными лицами. Соответствует требованиям ПП РФ № 1119.',
                'required_sections_json' => [
                    'Общие положения',
                    'Перечень информационных систем',
                    'Категории обрабатываемых данных',
                    'Уровни защищённости',
                    'Ответственные лица',
                ],
                'template_variables_json' => [
                    'organization_name',
                    'protection_level',
                ],
            ],
            [
                'code' => 'pd_consent_template',
                'document_type_code' => 'pd_consent',
                'name' => 'Согласие на обработку ПДн',
                'description' => 'Шаблон согласия субъекта на обработку персональных данных',
                'generation_prompt' => 'Сгенерируй форму согласия субъекта на обработку персональных данных. Документ должен соответствовать требованиям 152-ФЗ, ст. 9. Включи все обязательные элементы: субъект, оператор, цель обработки, перечень данных, перечень действий, срок, порядок отзыва. Оставь поля для заполнения субъектом.',
                'required_sections_json' => [
                    'Шапка документа',
                    'Сведения о субъекте',
                    'Сведения об операторе',
                    'Цель обработки',
                    'Перечень персональных данных',
                    'Перечень действий с данными',
                    'Срок действия согласия',
                    'Порядок отзыва согласия',
                    'Подпись субъекта',
                ],
                'template_variables_json' => [
                    'organization_name',
                    'legal_name',
                ],
            ],
            [
                'code' => 'site_privacy_policy_template',
                'document_type_code' => 'privacy_policy_site',
                'name' => 'Политика конфиденциальности сайта',
                'description' => 'Шаблон политики конфиденциальности для сайта',
                'generation_prompt' => 'Сгенерируй политику конфиденциальности для сайта организации. Документ должен соответствовать требованиям 152-ФЗ. Включи информацию о собираемых данных, cookie-файлах, целях сбора, правах пользователей, способах связи с оператором. Учитывай, что сайт может собирать данные через формы обратной связи.',
                'required_sections_json' => [
                    'Общие положения',
                    'Собираемые данные',
                    'Цели сбора данных',
                    'Cookie-файлы',
                    'Права пользователей',
                    'Защита данных',
                    'Изменения политики',
                    'Контактная информация',
                ],
                'template_variables_json' => [
                    'organization_name',
                    'has_website',
                ],
            ],
        ];

        foreach ($templates as $template) {
            $documentType = DocumentType::query()
                ->where('code', $template['document_type_code'])
                ->first();

            if (! $documentType) {
                $this->command->warn(
                    "Тип документа '{$template['document_type_code']}' не найден. Шаблон '{$template['code']}' пропущен."
                );
                continue;
            }

            DocumentTemplate::query()->updateOrCreate(
                ['code' => $template['code']],
                [
                    'document_type_id' => $documentType->id,
                    'name' => $template['name'],
                    'description' => $template['description'],
                    'generation_prompt' => $template['generation_prompt'],
                    'required_sections_json' => $template['required_sections_json'],
                    'template_variables_json' => $template['template_variables_json'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Шаблоны документов загружены.');
    }
}
