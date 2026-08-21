<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ShowTrainingExample extends Command
{
    protected $signature = 'ai:show-example
                            {file=golden_training_data.jsonl : Файл датасета}
                            {index=0 : Индекс примера (0-based)}
                            {--field= : Показать только поле: instruction, input, output}';

    protected $description = 'Показывает пример из датасета';

    public function handle(): int
    {
        $file = storage_path('app/' . $this->argument('file'));

        if (!File::exists($file)) {
            $this->error("Файл не найден: {$file}");
            return self::FAILURE;
        }

        $lines = array_filter(
            explode("\n", File::get($file)),
            fn ($line) => trim($line) !== ''
        );

        $index = (int) $this->argument('index');

        if ($index < 0 || $index >= count($lines)) {
            $this->error("Индекс {$index} вне диапазона (0-" . (count($lines) - 1) . ")");
            return self::FAILURE;
        }

        $data = json_decode($lines[$index], true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("Ошибка парсинга JSON: " . json_last_error_msg());
            return self::FAILURE;
        }

        $field = $this->option('field');

        if ($field && isset($data[$field])) {
            $this->line($data[$field]);
            return self::SUCCESS;
        }

        $this->info("=== ПРИМЕР #{$index} ===\n");

        $this->info("INSTRUCTION:");
        $this->line($data['instruction'] ?? 'N/A');

        $this->newLine();
        $this->info("INPUT (первые 2000 символов):");
        $this->line(mb_substr($data['input'] ?? 'N/A', 0, 2000));

        $this->newLine();
        $this->info("OUTPUT:");
        $output = json_decode($data['output'] ?? '{}', true);
        $this->line(json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        if (isset($data['metadata'])) {
            $this->newLine();
            $this->info("METADATA:");
            $this->line(json_encode($data['metadata'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }

        return self::SUCCESS;
    }
}
