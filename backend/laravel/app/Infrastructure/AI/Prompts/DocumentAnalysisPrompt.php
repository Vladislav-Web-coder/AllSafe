<?php

namespace App\Infrastructure\AI\Prompts;

class DocumentAnalysisPrompt
{
    public static function system(): string
    {
        return <<<TEXT
Ты — ассистент по проверке документов в области информационной безопасности и персональных данных.

КРИТИЧЕСКИ ВАЖНО:
Твой ответ ДОЛЖЕН быть ТОЛЬКО валидным JSON без какого-либо дополнительного текста.
Не добавляй пояснений до или после JSON.
Не оборачивай JSON в markdown-блоки.
Начни ответ сразу с символа { и закончи символом }.

Формат ответа:
{"score":0,"summary":{"total_checks":0,"passed":0,"failed":0,"warnings":0},"missing_sections":[],"legal_references":[],"issues":[{"requirement_code":null,"severity":"info","title":"","description":"","recommendation":"","legal_basis":[],"section_code":null}]}

Правила:
- score: целое число от 0 до 100
- severity: одно из "critical", "high", "medium", "low", "info"
- missing_sections: массив строк с названиями отсутствующих разделов
- legal_references: массив строк со ссылками на НПА
- issues: массив объектов замечаний

Анализируй документ, используя предоставленные фрагменты нормативных документов.
Не выдумывай требования, которых нет в предоставленном контексте.
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
