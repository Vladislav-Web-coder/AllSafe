<?php

namespace App\Console\Commands;

use App\Infrastructure\Embeddings\EmbeddingServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestLegalSearch extends Command
{
    protected $signature = 'knowledge:test-search
                            {query : Поисковый запрос}
                            {--limit=5 : Количество результатов}';

    protected $description = 'Тестирует векторный поиск по нормативной базе';

    public function handle(EmbeddingServiceInterface $embeddingService): int
    {
        $query = $this->argument('query');
        $limit = (int) $this->option('limit');

        $this->info("Запрос: {$query}");
        $this->info("Лимит: {$limit}");
        $this->newLine();

        // Создаём embedding для запроса
        $queryEmbedding = $embeddingService->embed($query);
        $vectorString = '[' . implode(',', $queryEmbedding) . ']';

        // Ищем похожие фрагменты
        $results = DB::connection('pgsql_knowledge')
            ->table('legal_chunks')
            ->join('legal_sources', 'legal_chunks.legal_source_id', '=', 'legal_sources.id')
            ->select(
                'legal_chunks.id',
                'legal_chunks.article',
                'legal_chunks.title as chunk_title',
                'legal_chunks.content',
                'legal_sources.title as source_title',
                'legal_sources.number as source_number',
                DB::raw("embedding <=> '{$vectorString}'::vector AS distance")
            )
            ->orderBy('distance')
            ->limit($limit)
            ->get();

        if ($results->isEmpty()) {
            $this->warn('Результаты не найдены.');
            return self::SUCCESS;
        }

        foreach ($results as $index => $result) {
            $this->info("--- Результат " . ($index + 1) . " ---");
            $this->line("Источник: {$result->source_title} ({$result->source_number})");
            $this->line("Статья: {$result->article}");
            $this->line("Заголовок: {$result->chunk_title}");
            $this->line("Расстояние: {$result->distance}");
            $this->line("Контент: " . mb_substr($result->content, 0, 200) . "...");
            $this->newLine();
        }

        return self::SUCCESS;
    }
}
