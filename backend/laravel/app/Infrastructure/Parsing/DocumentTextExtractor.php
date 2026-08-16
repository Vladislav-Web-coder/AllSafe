<?php

namespace App\Infrastructure\Parsing;

use Domain\Documents\Exceptions\UnsupportedDocumentFormatException;
use Smalot\PdfParser\Parser;
use ZipArchive;

class DocumentTextExtractor implements DocumentTextExtractorInterface
{
    public function extract(string $content, string $extension): string
    {
        $extension = strtolower(trim($extension));

        return match ($extension) {
            'pdf' => $this->extractPdf($content),
            'docx' => $this->extractDocx($content),
            'txt', 'md' => $this->extractPlain($content),
            default => throw new UnsupportedDocumentFormatException($extension),
        };
    }

    private function extractPdf(string $content): string
    {
        $parser = new Parser();

        $pdf = $parser->parseContent($content);

        $text = $pdf->getText();

        return $this->normalizeText($text);
    }

    private function extractDocx(string $content): string
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'docx_');

        if ($tempPath === false) {
            throw new RuntimeException('Не удалось создать временный файл для DOCX.');
        }

        file_put_contents($tempPath, $content);

        $zip = new ZipArchive();

        if ($zip->open($tempPath) !== true) {
            unlink($tempPath);

            throw new RuntimeException('Не удалось открыть DOCX как ZIP-архив.');
        }

        $documentXml = $zip->getFromName('word/document.xml');

        $zip->close();
        unlink($tempPath);

        if ($documentXml === false) {
            throw new RuntimeException('В DOCX не найден word/document.xml.');
        }

        preg_match_all('#<w:t[^>]*>(.*?)</w:t>#s', $documentXml, $matches);

        $texts = array_map(
            fn ($text) => html_entity_decode($text, ENT_QUOTES, 'UTF-8'),
            $matches[1] ?? []
        );

        return $this->normalizeText(implode(' ', $texts));
    }

    private function extractPlain(string $content): string
    {
        return $this->normalizeText($content);
    }

    private function normalizeText(string $text): string
    {
        // Убираем лишние пробелы и переносы.
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim($text ?? '');
    }
}
