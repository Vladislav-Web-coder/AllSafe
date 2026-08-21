<?php

namespace App\Console\Commands;

use App\Domain\Knowledge\Services\LegalSearchService;
use Illuminate\Console\Command;

class TestLegalSearch extends Command
{
    protected $signature = 'knowledge:test-search
                        {query : Поисковый запрос}
                        {--limit=5 : Количество результатов}
                        {--sources= : Фильтр по источникам (через запятую)}
                        {--categories= : Фильтр по категориям (через запятую)}
                        {--min-similarity=0.5 : Минимальное сходство}
                        {--hybrid : Использовать hybrid search (vector + text)}
                        {--vector-weight=0.7 : Вес векторного поиска}
                        {--text-weight=0.3 : Вес текстового поиска}';

    protected $description = 'Тест семантического поиска по базе знаний';

    public function handle(LegalSearchService $searchService): int
    {
        $query = $this->argument('query');
        $limit = (int) $this->option('limit');
        $minSimilarity = (float) $this->option('min-similarity');

        $sources = $this->option('sources')
            ? explode(',', $this->option('sources'))
            : null;

        $categories = $this->option('categories')
            ? explode(',', $this->option('categories'))
            : null;

        $this->info("Поиск: {$query}");
        $this->info("Параметры: limit={$limit}");

        if ($sources) {
            $this->info("Источники: " . implode(', ', $sources));
        }

        if ($categories) {
            $this->info("Категории: " . implode(', ', $categories));
        }

        $this->newLine();
        $start = microtime(true);

        if ($this->option('hybrid')) {
            $this->info('Режим: Hybrid Search (vector + text matching)');
            $vectorWeight = (float) $this->option('vector-weight');
            $textWeight = (float) $this->option('text-weight');

            $results = $searchService->hybridSearch(
                query: $query,
                limit: $limit,
                sourceNumbers: $sources,
                categories: $categories,
                vectorWeight: $vectorWeight,
                textWeight: $textWeight,
            );
        } else {
            $this->info('Режим: Vector Search');
            $this->info("Минимальная схожесть: {$minSimilarity}");

            $results = $searchService->search(
                query: $query,
                limit: $limit,
                sourceNumbers: $sources,
                categories: $categories,
                minSimilarity: $minSimilarity,
            );
        }

        $elapsed = round(microtime(true) - $start, 3);
        $this->info("Найдено: {$results->count()} результатов за {$elapsed}с");
        $this->newLine();

        if ($results->isEmpty()) {
            $this->warn($this->option('hybrid')
                ? 'Ничего не найдено.'
                : 'Ничего не найдено. Попробуйте уменьшить min-similarity.'
            );
            return self::SUCCESS;
        }

        foreach ($results as $index => $chunk) {
            $this->info("--- Результат " . ($index + 1) . " ---");
            $this->line("Источник: {$chunk->source->number} — {$chunk->source->title}");
            $this->line("Ссылка: {$chunk->getReference()}");
            $this->line("Заголовок: {$chunk->title}");

            // Специфичный вывод скоров для каждого режима
            if ($this->option('hybrid')) {
                $this->line(sprintf(
                    "Scores: vector=%.4f, text=%.4f, combined=%.4f",
                    $chunk->similarity,
                    $chunk->text_score,
                    $chunk->score
                ));
            } else {
                $this->line("Similarity: " . round($chunk->similarity, 4));
            }

            $this->newLine();
            $this->line(mb_substr($chunk->content, 0, 500));

            if (mb_strlen($chunk->content) > 500) {
                $this->line('...');
            }

            $this->newLine(2);
        }

        // Показываем отформатированный результат для LLM
        $this->newLine();
        $this->info('=== Формат для LLM (первые 2000 символов) ===');
        $formatted = $searchService->formatForLLM($results, 2000);
        $this->line($formatted);

        return self::SUCCESS;
    }

}
