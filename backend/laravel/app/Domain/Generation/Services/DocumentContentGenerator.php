<?php

namespace App\Domain\Generation\Services;

use App\Domain\Generation\Entities\DocumentTemplate;
use App\Domain\Organizations\Entities\Organization;
use App\Domain\Profiles\Entities\OrganizationProfile;
use App\Infrastructure\AI\AiClientInterface;
use App\Infrastructure\AI\Prompts\DocumentGenerationPrompt;
use Illuminate\Support\Facades\Log;

class DocumentContentGenerator
{
    public function __construct(
        private AiClientInterface $ai,
    ) {}

    public function generate(
        DocumentTemplate $template,
        Organization $organization,
        ?OrganizationProfile $profile,
    ): array {
        $prompt = DocumentGenerationPrompt::build(
            template: $template,
            organization: $organization,
            profile: $profile,
        );

        Log::info('DocumentContentGenerator: sending prompt', [
            'prompt_length' => mb_strlen($prompt),
            'template_code' => $template->code,
        ]);

        $result = $this->ai->generateDocumentContent($prompt);

        $content = $result['content'] ?? '';
        $sections = $result['sections'] ?? [];

        Log::info('DocumentContentGenerator: AI result', [
            'has_content' => ! empty($content),
            'content_length' => mb_strlen($content),
            'sections_count' => count($sections),
        ]);

        // Если sections пустой, но content не пустой — разбиваем content на секции
        if (empty($sections) && ! empty($content)) {
            // Проверяем, не является ли content сырым JSON
            $jsonParsed = $this->tryParseJsonContent($content);

            if ($jsonParsed !== null) {
                Log::info('DocumentContentGenerator: parsed JSON from content');
                return $jsonParsed;
            }

            // Разбиваем Markdown на секции
            $sections = $this->parseSectionsFromMarkdown($content);

            Log::info('DocumentContentGenerator: parsed sections from markdown', [
                'sections_count' => count($sections),
            ]);
        }

        // Валидируем секции
        $sections = $this->validateSections($sections, $template);

        $content = $this->cleanPlaceholders($content);

        foreach ($sections as &$section) {
            $section['content'] = $this->cleanPlaceholders($section['content'] ?? '');
        }
        unset($section);

        return [
            'content' => $content,
            'sections' => $sections,
        ];
    }

    /**
     * Пытается распарсить JSON из строки content.
     */
    private function tryParseJsonContent(string $content): ?array
    {
        $parser = new \App\Infrastructure\Ai\Support\LlmJsonParser();
        $decoded = $parser->parse($content);

        if (is_array($decoded) && (isset($decoded['content']) || isset($decoded['sections']))) {
            return [
                'content' => $decoded['content'] ?? '',
                'sections' => $decoded['sections'] ?? [],
            ];
        }

        return null;
    }

    /**
     * Разбивает Markdown-контент на секции по заголовкам.
     */
    private function parseSectionsFromMarkdown(string $content): array
    {
        $sections = [];
        $lines = explode("\n", $content);

        $currentTitle = null;
        $currentContent = [];

        foreach ($lines as $line) {
            if (preg_match('/^#{1,3}\s+(.+)$/', $line, $matches)) {
                if ($currentTitle !== null) {
                    $sections[] = [
                        'title' => $currentTitle,
                        'content' => trim(implode("\n", $currentContent)),
                    ];
                }

                $currentTitle = trim($matches[1]);
                $currentContent = [];
            } else {
                $currentContent[] = $line;
            }
        }

        if ($currentTitle !== null) {
            $sections[] = [
                'title' => $currentTitle,
                'content' => trim(implode("\n", $currentContent)),
            ];
        }

        if (empty($sections) && ! empty($content)) {
            $sections[] = [
                'title' => 'Основное содержание',
                'content' => $content,
            ];
        }

        return $sections;
    }

    /**
     * Валидирует секции: убирает пустые, добавляет недостающие.
     */
    private function validateSections(array $sections, DocumentTemplate $template): array
    {
        // Убираем секции с пустым контентом
        $sections = array_filter($sections, function ($section) {
            return ! empty($section['content']) && ! empty($section['title']);
        });

        // Переиндексируем
        $sections = array_values($sections);

        // Если секций нет, но есть обязательные разделы — создаём заглушки
        if (empty($sections) && ! empty($template->required_sections_json)) {
            foreach ($template->required_sections_json as $sectionTitle) {
                $sections[] = [
                    'title' => $sectionTitle,
                    'content' => 'Раздел требует доработки.',
                ];
            }
        }

        return $sections;
    }
    /**
     * Убирает placeholder'ы из контента.
     */
    private function cleanPlaceholders(string $text): string
    {
        // Убираем [ВСТАВЬТЕ ...], [ФИО ...], [Укажите ...] и т.д.
        $text = preg_replace('/\[(?:ВСТАВЬТЕ|УКАЖИТЕ|ЗАПОЛНИТЕ|ФИО|Должность|Контактные данные|Адрес)[^\]]*\]/iu', '', $text);

        // Убираем пустые строки, которые появились после удаления
        $text = preg_replace('/\n\s*\n\s*\n+/', "\n\n", $text);

        return $text;
    }
}
