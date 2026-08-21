<?php

namespace App\Console\Commands;

use App\Domain\Knowledge\Entities\LegalChunk;
use App\Domain\Knowledge\Services\EmbeddingServiceInterface;
use Illuminate\Console\Command;

class GenerateEmbeddingsCommand extends Command
{
    protected $signature = 'legal:embeddings
        {--source-number= : Номер источника (152-ФЗ, 21 и т.д.)}
        {--batch-size=5 : Размер батча для обработки}
        {--force : Перегенерировать все, включая уже имеющие embedding}';

    protected $description = 'Генерация vector embeddings для чанков НПА';

    public function __construct(
        // private EmbeddingServiceInterface $embeddingService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $query = LegalChunk::query()
            ->orderBy('legal_source_id')
            ->orderBy('chunk_index');

        if (! $this->option('force')) {
            // Фильтруем только чанки без embedding
            $query->where(function ($q) {
                $q->whereNull('embedding')
                    ->orWhere('embedding', '');
            });
        }

        if ($this->option('source-number')) {
            $sourceId = \App\Domain\Knowledge\Entities\LegalSource::query()
                ->where('number', $this->option('source-number'))
                ->value('id');

            if (! $sourceId) {
                $this->error("Источник не найден: {$this->option('source-number')}");
                return 1;
            }

            $query->where('legal_source_id', $sourceId);
        }

        $total = $query->count();

        if ($total === 0) {
            $this->info('Нет чанков для обработки.');
            return 0;
        }

        $this->info("Найдено чанков для обработки: {$total}");

        $batchSize = (int) $this->option('batch-size');
        $processed = 0;
        $failed = 0;

        $progress = $this->output->createProgressBar($total);
        $progress->start();

        $query->chunk($batchSize, function ($chunks) use ($batchSize, &$processed, &$failed, $progress) {
            $texts = $chunks->map(function ($chunk) {
                // Обогащаем текст контекстом для лучшего embedding
                $context = '';
                if ($chunk->source) {
                    $context = $chunk->source->number . ' "' . $chunk->source->title . '". ';
                }

                if ($chunk->title) {
                    $context .= $chunk->title . '. ';
                }

                return $context . $chunk->content;
            })->toArray();

            try {
                $embeddings = $this->embeddingService->generateBatch($texts);

                foreach ($chunks as $i => $chunk) {
                    $chunk->update([
                        'embedding' => $this->formatEmbedding($embeddings[$i]),
                    ]);
                    $processed++;
                    $progress->advance();
                }
            } catch (\Throwable $e) {
                $progress->advance(count($chunks));
                $failed += count($chunks);

                $this->newLine();
                $this->error("Ошибка батча: {$e->getMessage()}");
            }
        });

        $progress->finish();
        $this->newLine();

        $this->info("Обработано: {$processed}");
        if ($failed > 0) {
            $this->warn("Ошибок: {$failed}");
        }

        return 0;
    }

    /**
     * Форматирует embedding в формат pgvector: [0.1, 0.2, 0.3]
     */
    private function formatEmbedding(array $embedding): string
    {
        return '[' . implode(',', $embedding) . ']';
    }
}
