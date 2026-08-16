<?php

namespace App\Infrastructure\AI;

class FakeAiClient implements AiClientInterface
{
    public function analyzeDocument(array $payload): array
    {
        sleep(2);

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
    public function generateDocumentContent(string $prompt): array
    {
        sleep(2);

        return [
            'content' => "# Политика обработки персональных данных\n\n## 1. Общие положения\n\nНастоящая политика определяет порядок обработки персональных данных...\n\n## 2. Цели обработки\n\nОбработка персональных данных осуществляется в целях...\n\n## 3. Порядок уничтожения\n\nПерсональные данные подлежат уничтожению по достижении целей обработки...",
            'sections' => [
                [
                    'title' => 'Общие положения',
                    'content' => 'Настоящая политика определяет порядок обработки персональных данных и меры по обеспечению их безопасности.',
                ],
                [
                    'title' => 'Цели обработки',
                    'content' => 'Обработка персональных данных осуществляется в целях исполнения договорных обязательств перед клиентами и сотрудниками.',
                ],
                [
                    'title' => 'Порядок уничтожения',
                    'content' => 'Персональные данные подлежат уничтожению по достижении целей обработки в срок, не превышающий 30 дней.',
                ],
            ],
        ];
    }
}
