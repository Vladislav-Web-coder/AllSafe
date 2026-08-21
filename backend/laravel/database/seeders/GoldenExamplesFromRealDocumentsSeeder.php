<?php

namespace Database\Seeders;

use App\Domain\Training\Entities\GoldenExample;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class GoldenExamplesFromRealDocumentsSeeder extends Seeder
{
    public function run(): void
    {
        $this->createSchool26PolicyAnalysis();
        $this->createRokomnadzorConsentAnalysis();
        $this->createBadPolicyAnalysis();
        $this->createBadConsentAnalysis();
    }

    /**
     * Анализ ЭТАЛОННОЙ политики школы №26 (должна получить высокий score)
     */
    private function createSchool26PolicyAnalysis(): void
    {
        // Извлекаем текст из PDF (или используем уже извлечённый)
        $documentText = $this->extractSchool26PolicyText();

        $expectedOutput = [
            'score' => 94,
            'summary' => [
                'total_checks' => 25,
                'passed' => 23,
                'failed' => 0,
                'warnings' => 2,
            ],
            'missing_sections' => [],
            'legal_references' => [
                '152-ФЗ ст. 18.1',
                '152-ФЗ ст. 19',
                '152-ФЗ ст. 22.1',
                '152-ФЗ ст. 5',
                '152-ФЗ ст. 6',
                '152-ФЗ ст. 9',
                '152-ФЗ ст. 14',
            ],
            'issues' => [
                [
                    'requirement_code' => 'security_measures_detail',
                    'severity' => 'info',
                    'title' => 'Рекомендация по детализации мер защиты',
                    'description' => 'Раздел 10 содержит общие формулировки о мерах защиты. Рекомендуется детализировать организационные и технические меры в соответствии с Приказом ФСТЭК № 21.',
                    'recommendation' => 'Добавить конкретный перечень мер: идентификация и аутентификация, управление доступом, регистрация событий безопасности, антивирусная защита, обнаружение вторжений.',
                    'legal_basis' => [
                        '152-ФЗ ст. 19 ч. 2',
                        'Приказ ФСТЭК № 21',
                    ],
                    'section_code' => 'security',
                ],
                [
                    'requirement_code' => 'responsible_person_details',
                    'severity' => 'info',
                    'title' => 'Рекомендация по указанию ответственного лица',
                    'description' => 'Политика не содержит сведений о лице, ответственном за организацию обработки ПДн (ст. 22.1 152-ФЗ).',
                    'recommendation' => 'Добавить раздел с указанием ФИО, должности и контактных данных ответственного лица, либо сослаться на отдельный приказ о назначении.',
                    'legal_basis' => [
                        '152-ФЗ ст. 22.1 ч. 1',
                    ],
                    'section_code' => 'organization',
                ],
            ],
        ];

        GoldenExample::updateOrCreate(
            ['category' => 'analysis', 'document_type_code' => 'pd_policy', 'difficulty' => 'easy'],
            [
                'input_document' => $documentText,
                'organization_profile_json' => [
                    'name' => 'МАУ СОШ № 26 с углублённым изучением химии и биологии',
                    'type' => 'educational_institution',
                    'processes_personal_data' => true,
                    'subjects_count' => 2000,
                    'data_categories' => ['students', 'employees', 'parents'],
                    'has_website' => true,
                    'has_gis' => false,
                ],
                'rag_context_json' => [],
                'expected_output_json' => $expectedOutput,
                'annotated_by' => 'expert_system',
                'annotated_at' => now(),
                'is_verified' => true,
                'verified_by' => 'rokomnadzor_sample',
                'verified_at' => now(),
                'quality_score' => 5,
                'notes' => 'Эталонная политика с госуслуг. Соответствует требованиям 152-ФЗ с незначительными рекомендациями.',
            ]
        );

        $this->command->info('✓ Создан эталон: Политика школы №26 (score 94)');
    }

    /**
     * Анализ ЭТАЛОННОГО согласия Роскомнадзора
     */
    private function createRokomnadzorConsentAnalysis(): void
    {
        $documentText = $this->extractRokomnadzorConsentText();

        $expectedOutput = [
            'score' => 96,
            'summary' => [
                'total_checks' => 20,
                'passed' => 19,
                'failed' => 0,
                'warnings' => 1,
            ],
            'missing_sections' => [],
            'legal_references' => [
                '152-ФЗ ст. 9 ч. 1',
                '152-ФЗ ст. 9 ч. 4',
                '152-ФЗ ст. 6',
            ],
            'issues' => [
                [
                    'requirement_code' => 'consent_format_clarity',
                    'severity' => 'info',
                    'title' => 'Рекомендация по формату согласия',
                    'description' => 'Согласие является шаблоном с полями для заполнения. Рекомендуется добавить инструкцию для заполняющего.',
                    'recommendation' => 'Добавить краткую инструкцию в начале документа о порядке заполнения и подписания.',
                    'legal_basis' => [
                        '152-ФЗ ст. 9 ч. 1',
                    ],
                    'section_code' => 'consent_format',
                ],
            ],
        ];

        GoldenExample::updateOrCreate(
            ['category' => 'analysis', 'document_type_code' => 'consent_form', 'difficulty' => 'easy'],
            [
                'input_document' => $documentText,
                'organization_profile_json' => [
                    'processes_personal_data' => true,
                    'data_categories' => ['employees'],
                ],
                'rag_context_json' => [],
                'expected_output_json' => $expectedOutput,
                'annotated_by' => 'expert_system',
                'annotated_at' => now(),
                'is_verified' => true,
                'verified_by' => 'rokomnadzor_official',
                'verified_at' => now(),
                'quality_score' => 5,
                'notes' => 'Эталонное согласие сотрудника с сайта Роскомнадзора. Полностью соответствует ст. 9 152-ФЗ.',
            ]
        );

        $this->command->info('✓ Создан эталон: Согласие Роскомнадзора (score 96)');
    }

    /**
     * Анализ ПЛОХОЙ политики (намеренно нарушающей требования)
     */
    private function createBadPolicyAnalysis(): void
    {
        $badPolicyText = <<<TEXT
ПОЛИТИКА ОБРАБОТКИ ПЕРСОНАЛЬНЫХ ДАННЫХ
ООО "Плохая Компания"

1. Общие положения
Мы обрабатываем персональные данные.

2. Какие данные собираем
ФИО, паспортные данные, СНИЛС, ИНН, адрес, телефон, email, данные о здоровье, биометрические данные.

3. Зачем обрабатываем
Для наших нужд.

4. Кому передаём
Всем, кто попросит.

5. Как защищаем
Никак.
TEXT;

        $expectedOutput = [
            'score' => 8,
            'summary' => [
                'total_checks' => 20,
                'passed' => 0,
                'failed' => 18,
                'warnings' => 2,
            ],
            'missing_sections' => [
                'Правовые основания обработки',
                'Принципы обработки',
                'Права субъектов персональных данных',
                'Меры защиты персональных данных',
                'Сроки обработки и хранения',
                'Ответственное лицо за организацию обработки',
                'Порядок уничтожения данных',
                'Порядок обращения субъектов',
            ],
            'legal_references' => [
                '152-ФЗ ст. 5',
                '152-ФЗ ст. 6',
                '152-ФЗ ст. 9',
                '152-ФЗ ст. 10',
                '152-ФЗ ст. 11',
                '152-ФЗ ст. 14',
                '152-ФЗ ст. 18.1',
                '152-ФЗ ст. 19',
                '152-ФЗ ст. 22.1',
            ],
            'issues' => [
                [
                    'requirement_code' => 'excessive_data_collection',
                    'severity' => 'critical',
                    'title' => 'Избыточный сбор персональных данных',
                    'description' => 'Политика предусматривает сбор специальных категорий (здоровье) и биометрических данных без указания правовых оснований.',
                    'recommendation' => 'Исключить сбор специальных и биометрических данных, если нет законных оснований. При наличии оснований — указать их явно.',
                    'legal_basis' => [
                        '152-ФЗ ст. 5 ч. 4',
                        '152-ФЗ ст. 10',
                        '152-ФЗ ст. 11',
                    ],
                    'section_code' => 'data_minimization',
                ],
                [
                    'requirement_code' => 'unlawful_data_transfer',
                    'severity' => 'critical',
                    'title' => 'Незаконная передача данных третьим лицам',
                    'description' => 'Политика предусматривает передачу данных "всем, кто попросит" без правовых оснований и согласия субъектов.',
                    'recommendation' => 'Указать конкретные правовые основания передачи данных, перечень получателей, порядок получения согласия.',
                    'legal_basis' => [
                        '152-ФЗ ст. 6 ч. 1',
                        '152-ФЗ ст. 7',
                        '152-ФЗ ст. 9',
                    ],
                    'section_code' => 'data_transfer',
                ],
                [
                    'requirement_code' => 'missing_security_measures',
                    'severity' => 'critical',
                    'title' => 'Отсутствие мер защиты персональных данных',
                    'description' => 'Политика прямо указывает на отсутствие мер защиты данных.',
                    'recommendation' => 'Описать правовые, организационные и технические меры защиты в соответствии со ст. 19 152-ФЗ и Приказом ФСТЭК № 21.',
                    'legal_basis' => [
                        '152-ФЗ ст. 19',
                        '152-ФЗ ст. 18.1 ч. 1 п. 3',
                    ],
                    'section_code' => 'security',
                ],
                [
                    'requirement_code' => 'missing_legal_basis',
                    'severity' => 'high',
                    'title' => 'Отсутствие правовых оснований обработки',
                    'description' => 'Политика не указывает правовые основания обработки персональных данных.',
                    'recommendation' => 'Указать конкретные правовые основания из ст. 6 152-ФЗ для каждой цели обработки.',
                    'legal_basis' => [
                        '152-ФЗ ст. 6',
                    ],
                    'section_code' => 'legal_basis',
                ],
                [
                    'requirement_code' => 'vague_purposes',
                    'severity' => 'high',
                    'title' => 'Неконкретные цели обработки',
                    'description' => 'Цель "для наших нужд" не является конкретной и законной.',
                    'recommendation' => 'Указать конкретные цели обработки: исполнение договора, соблюдение закона, согласие субъекта и т.д.',
                    'legal_basis' => [
                        '152-ФЗ ст. 5 ч. 2',
                    ],
                    'section_code' => 'purposes',
                ],
                [
                    'requirement_code' => 'missing_subjects_rights',
                    'severity' => 'high',
                    'title' => 'Отсутствие информации о правах субъектов',
                    'description' => 'Политика не содержит информацию о правах субъектов ПДн.',
                    'recommendation' => 'Добавить раздел о правах субъектов: доступ, уточнение, блокирование, уничтожение, отзыв согласия.',
                    'legal_basis' => [
                        '152-ФЗ ст. 14',
                        '152-ФЗ ст. 15',
                        '152-ФЗ ст. 16',
                        '152-ФЗ ст. 17',
                    ],
                    'section_code' => 'rights',
                ],
                [
                    'requirement_code' => 'missing_responsible_person',
                    'severity' => 'medium',
                    'title' => 'Отсутствует ответственное лицо',
                    'description' => 'Не указано лицо, ответственное за организацию обработки ПДн.',
                    'recommendation' => 'Назначить ответственное лицо в соответствии со ст. 22.1 152-ФЗ.',
                    'legal_basis' => [
                        '152-ФЗ ст. 22.1',
                    ],
                    'section_code' => 'organization',
                ],
                [
                    'requirement_code' => 'missing_retention_periods',
                    'severity' => 'medium',
                    'title' => 'Отсутствуют сроки хранения',
                    'description' => 'Политика не определяет сроки обработки и хранения данных.',
                    'recommendation' => 'Указать сроки хранения для каждой категории данных или сослаться на законодательные требования.',
                    'legal_basis' => [
                        '152-ФЗ ст. 5 ч. 7',
                    ],
                    'section_code' => 'retention',
                ],
            ],
        ];

        GoldenExample::updateOrCreate(
            ['category' => 'analysis', 'document_type_code' => 'pd_policy', 'difficulty' => 'hard'],
            [
                'input_document' => $badPolicyText,
                'organization_profile_json' => [
                    'processes_personal_data' => true,
                ],
                'rag_context_json' => [],
                'expected_output_json' => $expectedOutput,
                'annotated_by' => 'expert_system',
                'annotated_at' => now(),
                'is_verified' => true,
                'verified_by' => 'system',
                'verified_at' => now(),
                'quality_score' => 5,
                'notes' => 'Намеренно плохая политика с множественными критическими нарушениями для обучения модели.',
            ]
        );

        $this->command->info('✓ Создан эталон: Плохая политика (score 8)');
    }

    /**
     * Анализ ПЛОХОГО согласия
     */
    private function createBadConsentAnalysis(): void
    {
        $badConsentText = "Согласие на обработку ПДн\n\nЯ согласен.\n\nПодпись: ___";

        $expectedOutput = [
            'score' => 5,
            'summary' => [
                'total_checks' => 15,
                'passed' => 0,
                'failed' => 15,
                'warnings' => 0,
            ],
            'missing_sections' => [
                'Данные субъекта (ФИО, паспорт)',
                'Данные оператора',
                'Цель обработки',
                'Перечень данных',
                'Перечень действий',
                'Срок действия',
                'Порядок отзыва',
            ],
            'legal_references' => ['152-ФЗ ст. 9'],
            'issues' => [
                [
                    'requirement_code' => 'consent_incomplete',
                    'severity' => 'critical',
                    'title' => 'Согласие не соответствует требованиям ст. 9 152-ФЗ',
                    'description' => 'Согласие не содержит обязательных реквизитов.',
                    'recommendation' => 'Привести в соответствие с ч. 4 ст. 9 152-ФЗ.',
                    'legal_basis' => ['152-ФЗ ст. 9 ч. 4'],
                    'section_code' => 'consent',
                ],
            ],
        ];

        GoldenExample::updateOrCreate(
            ['category' => 'analysis', 'document_type_code' => 'consent_form', 'difficulty' => 'hard'],
            [
                'input_document' => $badConsentText,
                'organization_profile_json' => [],
                'rag_context_json' => [],
                'expected_output_json' => $expectedOutput,
                'annotated_by' => 'expert_system',
                'annotated_at' => now(),
                'is_verified' => true,
                'verified_by' => 'system',
                'verified_at' => now(),
                'quality_score' => 5,
                'notes' => 'Минимальное согласие для обучения модели распознаванию нарушений.',
            ]
        );

        $this->command->info('✓ Создан эталон: Плохое согласие (score 5)');
    }

    private function extractSchool26PolicyText(): string
    {
        // Возвращаем извлечённый текст из PDF
        return Storage::disk('local')->exists('golden_documents/policies/school_26_good.txt')
            ? Storage::disk('local')->get('golden_documents/policies/school_26_good.txt')
            : 'Текст политики школы №26 (необходимо извлечь из PDF)';
    }

    private function extractRokomnadzorConsentText(): string
    {
        return Storage::disk('local')->exists('golden_documents/consents/rokomnadzor_employee_good.txt')
            ? Storage::disk('local')->get('golden_documents/consents/rokomnadzor_employee_good.txt')
            : 'Текст согласия Роскомнадзора (необходимо извлечь из DOC)';
    }
}
