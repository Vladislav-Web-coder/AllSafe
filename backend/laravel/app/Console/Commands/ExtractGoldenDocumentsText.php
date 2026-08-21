<?php

namespace App\Console\Commands;

use App\Infrastructure\Parsing\DocumentTextExtractorInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ExtractGoldenDocumentsText extends Command
{
    protected $signature = 'golden:extract-text';
    protected $description = 'Извлекает текст из эталонных документов';

    public function handle(DocumentTextExtractorInterface $extractor): int
    {
        $basePath = storage_path('app/golden_documents');

        $files = [
            'policies/school_26_good.pdf',
            'consents/rokomnadzor_employee_good.pdf',
        ];

        foreach ($files as $file) {
            $fullPath = "{$basePath}/{$file}";

            if (!file_exists($fullPath)) {
                $this->warn("Файл не найден: {$file}");
                continue;
            }

            $this->info("Извлечение: {$file}");

            $content = file_get_contents($fullPath);
            $extension = pathinfo($file, PATHINFO_EXTENSION);

            $text = $extractor->extract($content, $extension);

            $outputPath = str_replace('.' . $extension, '.txt', $file);
            Storage::disk('local')->put("golden_documents/{$outputPath}", $text);

            $this->info("  Сохранено: {$outputPath} (" . mb_strlen($text) . " символов)");
        }

        return self::SUCCESS;
    }
}
