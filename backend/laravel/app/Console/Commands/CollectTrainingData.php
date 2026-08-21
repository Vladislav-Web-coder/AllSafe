<?php

namespace App\Console\Commands;

use App\Domain\Training\Entities\GoldenExample;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CollectTrainingData extends Command
{
    protected $signature = 'ai:collect-training-data
                            {--source=golden : Источник данных: golden, logs, mixed}
                            {--limit=100 : Максимум примеров из логов}
                            {--min-score=70 : Минимальный score для логов}
                            {--output=training_data.jsonl : Файл вывода}
                            {--require-verification : Только проверенные примеры}';

    protected $description = 'Собирает датасет для дообучения LLM';

    public function handle(): int
    {
        $source = $this->option('source');
        $outputFile = storage_path('app/' . $this->option('output'));

        $this->info("Сбор датасета из источника: {$source}");

        $examples = match ($source) {
            'golden' => $this->collectGoldenExamples(),
            'logs' => $this->collectFromLogs(),
            'mixed' => $this->collectMixed(),
            default => collect(),
        };

        if ($examples->isEmpty()) {
            $this->error('Не удалось собрать примеры.');
            return self::FAILURE;
        }

        // Сохраняем
        File::ensureDirectoryExists(dirname($outputFile));

        $handle = fopen($outputFile, 'w');
        foreach ($examples as $example) {
            fwrite($handle, json_encode($example, JSON_UNESCAPED_UNICODE) . "\n");
        }
        fclose($handle);

        $this->info("Датасет сохранён: {$outputFile}");
        $this->info("Примеров: {$examples->count()}");

        $this->showStats($examples);

        return self::SUCCESS;
    }

    /**
     * Собирает только эталонные примеры (рекомендуется).
     */
    private function collectGoldenExamples()
    {
        $query = GoldenExample::query();

        if ($this->option('require-verification')) {
            $query->verified();
        }

        $examples = $query->get();

        $this->info("Найдено эталонных примеров: {$examples->count()}");

        return $examples->map(function (GoldenExample $example) {
            return [
                'instruction' => $this->getInstruction($example),
                'input' => $this->buildInput($example),
                'output' => json_encode($example->expected_output_json, JSON_UNESCAPED_UNICODE),
                'metadata' => [
                    'source' => 'golden',
                    'example_id' => $example->id,
                    'category' => $example->category,
                    'document_type' => $example->document_type_code,
                    'difficulty' => $example->difficulty,
                    'quality_score' => $example->quality_score,
                ],
            ];
        });
    }

    /**
     * Собирает из логов (не рекомендуется без ручной валидации).
     */
    private function collectFromLogs()
    {
        $this->warn("ВНИМАНИЕ: Сбор из логов может содержать ошибки модели!");
        $this->warn("Рекомендуется использовать --source=golden или вручную проверить каждый пример.");

        if (!$this->confirm('Продолжить сбор из логов?')) {
            return collect();
        }

        // Существующая логика сбора из логов
        return $this->collectFromLogsInternal();
    }

    /**
     * Смешанный датасет: эталоны + проверенные логи.
     */
    private function collectMixed()
    {
        $golden = $this->collectGoldenExamples();
        $logs = $this->collectFromLogsInternal();

        return $golden->concat($logs);
    }

    private function collectFromLogsInternal()
    {
        $limit = (int) $this->option('limit');
        $minScore = (int) $this->option('min-score');

        $runs = \App\Domain\Analysis\Entities\AnalysisRun::query()
            ->with(['document.type', 'issues'])
            ->where('status', 'completed')
            ->whereNotNull('score')
            ->where('score', '>=', $minScore)
            ->orderByDesc('score')
            ->limit($limit)
            ->get();

        return $runs->map(function ($run) {
            return $this->buildFromRun($run);
        })->filter();
    }

    private function buildFromRun($run): ?array
    {
        // Существующая логика
        return null;
    }

    private function getInstruction(GoldenExample $example): string
    {
        return match ($example->category) {
            'analysis' => 'Проанализируй документ на соответствие требованиям законодательства РФ о персональных данных.',
            'generation' => 'Создай документ на основе предоставленного шаблона и информации об организации.',
            default => 'Выполни задачу.',
        };
    }

    private function buildInput(GoldenExample $example): string
    {
        $input = $example->input_document;

        if ($example->organization_profile_json) {
            $input .= "\n\nПрофиль организации:\n" . json_encode($example->organization_profile_json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        return $input;
    }

    private function showStats($examples): void
    {
        $this->info("\n=== СТАТИСТИКА ДАТАСЕТА ===\n");

        $bySource = $examples->groupBy(fn ($e) => $e['metadata']['source'] ?? 'unknown');
        $byCategory = $examples->groupBy(fn ($e) => $e['metadata']['category'] ?? 'unknown');

        $this->table(
            ['Метрика', 'Значение'],
            [
                ['Всего примеров', $examples->count()],
                ['Из эталонных', $bySource->get('golden')?->count() ?? 0],
                ['Из логов', $bySource->get('logs')?->count() ?? 0],
                ['Для анализа', $byCategory->get('analysis')?->count() ?? 0],
                ['Для генерации', $byCategory->get('generation')?->count() ?? 0],
            ]
        );
    }
}
