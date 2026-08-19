<?php

namespace App\Domain\Generation\Services;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Log;

class DocxGenerator
{
    public function generate(
        string $title,
        array $sections,
        string $organizationName,
    ): string {
        Log::info('DocxGenerator: generate', [
            'title' => $title,
            'sections_count' => count($sections),
            'section_titles' => array_column($sections, 'title'),
        ]);

        $phpWord = new PhpWord();

        $phpWord->getDocInfo()->setTitle($title);
        $phpWord->getDocInfo()->setCreator('AllSafe');

        // Титульная страница
        $titleSection = $phpWord->addSection();

        $titleSection->addText(
            $organizationName,
            ['bold' => true, 'size' => 14],
            ['alignment' => 'center', 'spaceAfter' => 200]
        );

        $titleSection->addText(
            $title,
            ['bold' => true, 'size' => 16],
            ['alignment' => 'center', 'spaceAfter' => 400]
        );

        $titleSection->addText(
            'Дата утверждения: «___» _____________ ' . date('Y') . ' г.',
            ['size' => 11],
            ['alignment' => 'right', 'spaceBefore' => 600]
        );

        // Разделы документа
        $contentSection = $phpWord->addSection();

        if (empty($sections)) {
            Log::warning('DocxGenerator: no sections to write');

            $contentSection->addText(
                'Содержимое документа не было сгенерировано.',
                ['size' => 11, 'italic' => true],
                ['alignment' => 'center', 'spaceBefore' => 400]
            );
        } else {
            foreach ($sections as $index => $section) {
                $sectionTitle = trim($section['title'] ?? "Раздел " . ($index + 1));
                $sectionContent = trim($section['content'] ?? '');

                if (empty($sectionContent)) {
                    continue;
                }

                $contentSection->addText(
                    ($index + 1) . '. ' . $sectionTitle,
                    ['bold' => true, 'size' => 13],
                    ['spaceBefore' => 300, 'spaceAfter' => 100]
                );

                // Разбиваем контент на параграфы
                $paragraphs = preg_split('/\n\s*\n/', $sectionContent);

                foreach ($paragraphs as $paragraph) {
                    $cleanText = trim($paragraph);

                    if (empty($cleanText)) {
                        continue;
                    }

                    // Убираем markdown-разметку
                    $cleanText = preg_replace('/^#{1,6}\s+/', '', $cleanText);
                    $cleanText = preg_replace('/\*\*(.+?)\*\*/s', '$1', $cleanText);
                    $cleanText = preg_replace('/\*(.+?)\*/s', '$1', $cleanText);
                    $cleanText = preg_replace('/`(.+?)`/s', '$1', $cleanText);

                    // Обрабатываем списки
                    $cleanText = preg_replace('/^\s*[-*+]\s+/m', '• ', $cleanText);
                    $cleanText = preg_replace('/^\s*\d+\.\s+/m', '', $cleanText);

                    // Убираем лишние переносы
                    $cleanText = preg_replace('/\n+/', "\n", $cleanText);
                    $cleanText = trim($cleanText);

                    if (empty($cleanText)) {
                        continue;
                    }

                    $contentSection->addText(
                        $cleanText,
                        ['size' => 11],
                        ['alignment' => 'both', 'spaceAfter' => 120]
                    );
                }
            }
        }

        // Сохраняем во временный файл
        $tempPath = tempnam(sys_get_temp_dir(), 'docx_') . '.docx';

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempPath);

        Log::info('DocxGenerator: saved', [
            'path' => $tempPath,
            'size' => filesize($tempPath),
        ]);

        return $tempPath;
    }
}
