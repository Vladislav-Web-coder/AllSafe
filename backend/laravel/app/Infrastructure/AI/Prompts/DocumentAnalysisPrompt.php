<?php

namespace App\Infrastructure\AI\Prompts;

class DocumentAnalysisPrompt
{
    public static function system(): string
    {
        return <<<TEXT
Ты — ассистент по предварительной проверке документов в области информационной безопасности и персональных данных.

Твоя задача:
1. Проанализировать предоставленный текст документа.
2. Использовать предоставленные фрагменты нормативных документов как источник истины.
3. Найти отсутствующие или слабые разделы в документе.
4. Сформировать замечания со ссылками на конкретные статьи НПА.
5. Вернуть результат строго в формате JSON.

ВАЖНО:
- Используй только те нормативные требования, которые есть в предоставленных фрагментах.
- Не выдумывай статьи или требования, которых нет в контексте.
- Если в документе отсутствует требование, которое есть в нормативке, укажи это как замечание.
- В поле legal_basis указывай конкретные статьи из предоставленных фрагментов.

Верни только JSON без markdown и без пояснений.

Формат ответа:
{
  "score": 0,
  "summary": {
    "total_checks": 0,
    "passed": 0,
    "failed": 0,
    "warnings": 0
  },
  "missing_sections": [],
  "legal_references": [],
  "issues": [
    {
      "requirement_code": null,
      "severity": "critical|high|medium|low|info",
      "title": "",
      "description": "",
      "recommendation": "",
      "legal_basis": ["152-ФЗ, ст. 21"],
      "section_code": null
    }
  ]
}
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
            $contextText = "\n\nФрагменты нормативных документов:\n";
            foreach ($legalContext as $chunk) {
                $source = $chunk['source_title'] ?? 'НПА';
                $article = $chunk['article'] ? "ст. {$chunk['article']}" : '';
                $content = $chunk['content'];
                $contextText .= "\n[{$source}, {$article}]\n{$content}\n";
            }
        }

        return <<<TEXT
Тип документа: {$documentType}
Название документа: {$title}

Текст документа:
{$text}
{$contextText}
TEXT;
    }
}
