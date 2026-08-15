<?php

namespace App\Infrastructure\AI;

class FakeAiClient implements AiClientInterface
{
    public function analyzeDocument(array $payload): array
    {
        sleep(3);

        return [
            'score' => 68,

            'summary' => [
                'total_checks' => 14,
                'passed' => 9,
                'failed' => 4,
                'warnings' => 1,
            ],

            'missing_sections' => [
                'Порядок уничтожения ПДн',
                'Ответственное лицо',
            ],

            'legal_references' => [
                '152-ФЗ',
                'ПП РФ № 1119',
            ],

            'issues' => [
                [
                    'requirement_code' => 'PD-DESTROY-001',
                    'severity' => 'high',
                    'title' => 'Отсутствует порядок уничтожения ПДн',
                    'description' => 'В документе не описан порядок уничтожения персональных данных.',
                    'recommendation' => 'Добавить раздел с порядком уничтожения ПДн.',
                    'legal_basis' => [
                        '152-ФЗ',
                    ],
                    'section_code' => 'pd_destruction',
                ],
                [
                    'requirement_code' => 'PD-RESPONSIBLE-001',
                    'severity' => 'high',
                    'title' => 'Не указано ответственное лицо',
                    'description' => 'Не указано лицо, ответственное за организацию обработки ПДн.',
                    'recommendation' => 'Добавить должность и ФИО ответственного лица.',
                    'legal_basis' => [
                        '152-ФЗ',
                    ],
                    'section_code' => 'pd_responsible',
                ],
                [
                    'requirement_code' => 'PD-TERMS-001',
                    'severity' => 'medium',
                    'title' => 'Не указаны сроки хранения данных',
                    'description' => 'В документе отсутствуют сроки хранения персональных данных.',
                    'recommendation' => 'Добавить таблицу сроков хранения по категориям данных.',
                    'legal_basis' => [
                        '152-ФЗ',
                    ],
                    'section_code' => 'pd_storage_terms',
                ],
                [
                    'requirement_code' => 'DOC-VERSION-001',
                    'severity' => 'info',
                    'title' => 'Рекомендуется указать версию документа',
                    'description' => 'Рекомендуется добавить версию и дату утверждения документа.',
                    'recommendation' => 'Добавить реквизиты версии документа.',
                    'legal_basis' => [],
                    'section_code' => 'doc_version',
                ],
            ],

            'model_provider' => 'fake',
            'model_name' => 'stub-analyzer-v1',
        ];
    }
}
