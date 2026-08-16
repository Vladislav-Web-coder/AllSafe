<?php

namespace App\Console\Commands;

use App\Domain\Knowledge\Entities\LegalChunk;
use App\Infrastructure\Embeddings\EmbeddingServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegenerateLegalChunkEmbeddings extends Command
{
    protected $signature = 'knowledge:regenerate-embeddings
                            {--batch-size=20 : Количество чанков за один запрос к embedding-сервису}
                            {--source-id= : Перегенерировать только для конкретного legal_source_id}';

    protected $description = 'Перегенерирует embeddings для всех фрагментов нормативных документов';

    public function handle(EmbeddingServiceInterface $embeddingService): int
    {
        $batchSize = (int) $this->option('batch-size');
        $sourceId = $this->option('source-id');

        $query = LegalChunk::on('pgsql_knowledge')
            ->orderBy('id');

        if ($sourceId) {
            $query->where('legal_source_id', $sourceId);
        }

        $total = $query->count();

        if ($total === 0) {
            $this->warn('Фрагменты не найдены. Сначала загрузите НПА через seeder.');
            return self::FAILURE;
        }

        $this->info("Найдено фрагментов: {$total}");
        $this->info("Размер батча: {$batchSize}");
        $this->newLine();

        $processed = 0;
        $errors = 0;

        $query->chunk($batchSize, function ($chunks) use ($embeddingService, &$processed, &$errors) {
            $texts = $chunks->pluck('content')->toArray();

            try {
                $embeddings = $embeddingService->embedBatch($texts);

                foreach ($chunks as $index => $chunk) {
                    if (isset($embeddings[$index])) {
                        $vectorString = '[' . implode(',', $embeddings[$index]) . ']';

                        DB::connection('pgsql_knowledge')
                            ->table('legal_chunks')
                            ->where('id', $chunk->id)
                            ->update([
                                'embedding' => $vectorString,
                                'updated_at' => now(),
                            ]);

                        $processed++;
                    }
                }
            } catch (\Throwable $e) {
                $errors++;
                $this->error("Ошибка батча: {$e->getMessage()}");
                Log::error('RegenerateLegalChunkEmbeddings batch error', [
                    'error' => $e->getMessage(),
                    'chunk_ids' => $chunks->pluck('id')->toArray(),
                ]);
            }

            $this->output->write("\rОбработано: {$processed} / Ошибки: {$errors}");
        });

        $this->newLine(2);
        $this->info("Готово. Обработано: {$processed}, Ошибки: {$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
