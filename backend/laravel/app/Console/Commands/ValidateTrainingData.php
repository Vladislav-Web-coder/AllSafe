<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ValidateTrainingData extends Command
{
    protected $signature = 'ai:validate-training-data
                            {file=training_data.jsonl : Путь к файлу датасета}';

    protected $description = 'Валидирует датасет для дообучения';

    public function handle(): int
    {
        $file = storage_path('app/' . $this->argument('file'));

        if (!File::exists($file)) {
            $this->error("Файл не найден: {$file}");
            $this->info("Доступные файлы:");

            $jsonlFiles = File::glob(storage_path('app/*.jsonl'));
            $allFiles = File::glob(storage_path('app/golden*'));

            foreach (array_merge($jsonlFiles, $allFiles) as $f) {
                $this->line("  - " . basename($f));
            }

            return self::FAILURE;
        }

        $this->info("Валидация датасета: {$file}");
        $this->info("Размер файла: " . File::size($file) . " байт");

        $content = File::get($file);

        if (empty(trim($content))) {
            $this->error("Файл пустой.");
            return self::FAILURE;
        }

        $lines = array_filter(
            explode("\n", $content),
            fn ($line) => trim($line) !== ''
        );

        if (empty($lines)) {
            $this->error("Файл не содержит данных.");
            return self::FAILURE;
        }

        $this->info("Найдено строк: " . count($lines));

        $valid = 0;
        $invalid = 0;
        $errors = [];

        foreach ($lines as $i => $line) {
            $lineNum = $i + 1;

            $data = json_decode($line, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = "Строка {$lineNum}: невалидный JSON - " . json_last_error_msg();
                $invalid++;
                continue;
            }

            // Проверяем обязательные поля
            $requiredTopLevel = ['instruction', 'input', 'output'];
            $missing = array_diff($requiredTopLevel, array_keys($data));

            if (!empty($missing)) {
                $errors[] = "Строка {$lineNum}: отсутствуют поля: " . implode(', ', $missing);
                $invalid++;
                continue;
            }

            // Проверяем output JSON
            $output = json_decode($data['output'], true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = "Строка {$lineNum}: output не является валидным JSON";
                $invalid++;
                continue;
            }

            // Проверяем структуру output
            $requiredFields = ['score', 'issues'];
            foreach ($requiredFields as $field) {
                if (!isset($output[$field])) {
                    $errors[] = "Строка {$lineNum}: в output отсутствует поле '{$field}'";
                    $invalid++;
                    continue 2;
                }
            }

            // Проверяем score
            if (!is_int($output['score']) || $output['score'] < 0 || $output['score'] > 100) {
                $errors[] = "Строка {$lineNum}: score должен быть целым числом 0-100, получено: " . var_export($output['score'], true);
                $invalid++;
                continue;
            }

            // Проверяем issues
            if (!is_array($output['issues'])) {
                $errors[] = "Строка {$lineNum}: issues должен быть массивом";
                $invalid++;
                continue;
            }

            $validSeverities = ['critical', 'high', 'medium', 'low', 'info'];

            foreach ($output['issues'] as $j => $issue) {
                if (!is_array($issue)) {
                    $errors[] = "Строка {$lineNum}, issue {$j}: не является объектом";
                    $invalid++;
                    continue 2;
                }

                if (!isset($issue['severity']) || !isset($issue['title'])) {
                    $errors[] = "Строка {$lineNum}, issue {$j}: отсутствуют severity или title";
                    $invalid++;
                    continue 2;
                }

                if (!in_array($issue['severity'], $validSeverities)) {
                    $errors[] = "Строка {$lineNum}, issue {$j}: невалидный severity '{$issue['severity']}'";
                    $invalid++;
                    continue 2;
                }
            }

            $valid++;
        }

        $this->newLine();
        $this->info("=== РЕЗУЛЬТАТЫ ВАЛИДАЦИИ ===\n");

        $totalLines = count($lines);
        $percentage = $totalLines > 0 ? round($valid / $totalLines * 100, 2) : 0;

        $this->table(
            ['Метрика', 'Значение'],
            [
                ['Всего строк', $totalLines],
                ['Валидных', $valid],
                ['Невалидных', $invalid],
                ['Процент валидных', $percentage . '%'],
            ]
        );

        if (!empty($errors)) {
            $this->newLine();
            $this->error("Ошибки (" . count($errors) . "):");
            foreach (array_slice($errors, 0, 15) as $error) {
                $this->line("  - {$error}");
            }

            if (count($errors) > 15) {
                $this->line("  ... и ещё " . (count($errors) - 15) . " ошибок");
            }
        }

        if ($invalid === 0) {
            $this->newLine();
            $this->info("✓ Датасет полностью валиден!");
        }

        return $invalid === 0 ? self::SUCCESS : self::FAILURE;
    }
}
