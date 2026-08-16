<?php

namespace App\Domain\Knowledge\Services;

use App\Domain\Knowledge\Entities\LegalSource;
use App\Domain\Knowledge\Repositories\LegalChunkRepositoryInterface;
use App\Infrastructure\Embeddings\EmbeddingServiceInterface;
use Illuminate\Support\Facades\DB;

class LegalDocumentIngestionService
{
    public function __construct(
        private LegalChunkRepositoryInterface $chunks,
        private EmbeddingServiceInterface $embeddings,
    ) {}

    public function ingest(
        string $sourceType,
        string $title,
        string $number,
        array $sections,
        ?string $sourceUrl = null,
    ): LegalSource {
        return DB::connection('pgsql_knowledge')->transaction(function () use (
            $sourceType,
            $title,
            $number,
            $sections,
            $sourceUrl,
        ) {
            $source = LegalSource::query()->create([
                'source_type' => $sourceType,
                'title' => $title,
                'number' => $number,
                'source_url' => $sourceUrl,
                'is_active' => true,
            ]);

            $this->chunks->deleteBySource($source->id);

            foreach ($sections as $index => $section) {
                $content = $section['content'] ?? '';
                $article = $section['article'] ?? null;
                $part = $section['part'] ?? null;
                $clause = $section['clause'] ?? null;
                $sectionTitle = $section['title'] ?? null;

                $embedding = $this->embeddings->embed($content);

                $this->chunks->create([
                    'legal_source_id' => $source->id,
                    'chunk_index' => $index,
                    'article' => $article,
                    'part' => $part,
                    'clause' => $clause,
                    'title' => $sectionTitle,
                    'content' => $content,
                    'metadata_json' => [
                        'source_title' => $title,
                        'source_number' => $number,
                    ],
                    'embedding' => '[' . implode(',', $embedding) . ']',
                ]);
            }

            return $source;
        });
    }
}
