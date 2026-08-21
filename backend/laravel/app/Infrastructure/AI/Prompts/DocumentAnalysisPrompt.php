<?php

namespace App\Infrastructure\AI\Prompts;

class DocumentAnalysisPrompt
{
    public static function system(): string
    {
        return <<<TEXT
Ты — эксперт-аудитор по compliance и защите персональных данных в Российской Федерации.

Твоя задача — проанализировать документ на соответствие требованиям законодательства РФ.

КРИТИЧЕСКИ ВАЖНО:
1. Ответ ДОЛЖЕН быть ТОЛЬКО валидным JSON без пояснений
2. Не оборачивай JSON в markdown-блоки
3. Начни с { и закончи }

Формат ответа:
{
  "score": 0-100,
  "summary": {
    "total_checks": 0,
    "passed": 0,
    "failed": 0,
    "warnings": 0
  },
  "missing_sections": ["раздел1", "раздел2"],
  "legal_references": ["152-ФЗ ст. 5", "152-ФЗ ст. 9"],
  "issues": [
    {
      "requirement_code": null,
      "severity": "critical|high|medium|low|info",
      "title": "Краткое название замечания",
      "description": "Подробное описание нарушения",
      "recommendation": "Конкретная рекомендация по исправлению",
      "legal_basis": ["152-ФЗ ст. 5 ч. 1", "Приказ ФСТЭК № 21"],
      "section_code": null
    }
  ]
}

Правила анализа:
- Используй ТОЛЬКО предоставленные фрагменты НПА
- В поле legal_basis указывай точные ссылки из контекста (например: "152-ФЗ ст. 9 ч. 4")
- severity: critical (нарушение закона), high (серьёзное несоответствие), medium (частичное несоответствие), low (рекомендация), info (информационное)
- Не выдумывай требования, которых нет в контексте
- Если документ соответствует требованиям — верни высокий score и пустой issues
TEXT;
    }

    public static function user(array $payload): string
    {
        $title = $payload['title'] ?? 'Документ';
        $documentType = $payload['document_type_name'] ?? 'Не указан';
        $text = $payload['document_text'] ?? '';
        $legalContext = $payload['legal_context'] ?? [];

        $contextText = '';
        if (! empty($legalContext)) {
            $contextText = "\n\n=== ФРАГМЕНТЫ НОРМАТИВНЫХ ДОКУМЕНТОВ ===\n";
            foreach ($legalContext as $i => $chunk) {
                $reference = $chunk['reference'] ?? 'НПА';
                $content = $chunk['content'];
                $score = $chunk['relevance_score'] ?? 0;
                $contextText .= "\n[{$i}] {$reference} (relevance: {$score})\n{$content}\n";
            }
            $contextText .= "\n=== КОНЕЦ ФРАГМЕНТОВ ===\n";
        }

        return <<<TEXT
Тип документа: {$documentType}
Название: {$title}

=== ТЕКСТ ДОКУМЕНТА ===
{$text}

{$contextText}

Проанализируй документ и верни JSON с результатами.
TEXT;
    }
}
