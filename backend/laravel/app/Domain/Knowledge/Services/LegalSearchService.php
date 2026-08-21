<?php

namespace App\Domain\Knowledge\Services;

use App\Domain\Knowledge\Entities\LegalChunk;
use App\Domain\Knowledge\Entities\LegalSource;
use App\Infrastructure\Embeddings\EmbeddingServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class LegalSearchService
{
    public function __construct(
        private EmbeddingServiceInterface $embeddingService,
    ) {}

    /**
     * Семантический поиск по базе знаний.
     *
     * @param string $query Запрос пользователя
     * @param int $limit Количество результатов
     * @param array|null $sourceNumbers Фильтр по номерам документов (['152-ФЗ', '187-ФЗ'])
     * @param array|null $categories Фильтр по категориям (['personal_data', 'critical_infrastructure'])
     * @param float $minSimilarity Минимальное сходство (0-1)
     * @return Collection<LegalChunk>
     */
    /**
     * Семантический поиск по базе знаний (теперь использует hybrid search).
     */
    public function search(
        string $query,
        int $limit = 5,
        ?array $sourceNumbers = null,
        ?array $categories = null,
        float $minSimilarity = 0.5,
    ): Collection {
        $results = $this->hybridSearch($query, $limit * 3, $sourceNumbers, $categories);

        // Фильтруем по minSimilarity (используем vector similarity)
        return $results->filter(fn ($chunk) => $chunk->similarity >= $minSimilarity)->take($limit);
    }

    /**
     * Гибридный поиск: vector similarity + text matching (BM25-like).
     *
     * @param string $query Запрос пользователя
     * @param int $limit Количество результатов
     * @param array|null $sourceNumbers Фильтр по номерам документов
     * @param array|null $categories Фильтр по категориям
     * @param float $vectorWeight Вес векторного поиска (0-1)
     * @param float $textWeight Вес текстового поиска (0-1)
     * @return Collection<LegalChunk>
     */
    public function hybridSearch(
        string $query,
        int $limit = 10,
        ?array $sourceNumbers = null,
        ?array $categories = null,
        float $vectorWeight = 0.7,
        float $textWeight = 0.3,
    ): Collection {
        // Генерируем embedding для запроса
        $queryEmbedding = $this->embeddingService->embed($query);
        $queryVector = '[' . implode(',', $queryEmbedding) . ']';

        // Извлекаем ключевые слова для текстового поиска
        $keywords = $this->extractKeywords($query);

        // Векторный поиск (топ 50 для reranking)
        $vectorResults = $this->vectorSearch($queryVector, 50, $sourceNumbers, $categories);

        // Текстовый поиск (топ 50 для reranking)
        $textResults = $this->textSearch($keywords, 50, $sourceNumbers, $categories);

        // Объединяем и нормализуем scores
        $combined = $this->combineResults($vectorResults, $textResults, $vectorWeight, $textWeight);

        // Сортируем по итоговому score
        $combined = $combined->sortByDesc('score')->take($limit);

        // Загружаем полные модели
        $chunkIds = $combined->pluck('id')->toArray();

        $chunks = LegalChunk::query()
            ->with('source')
            ->whereIn('id', $chunkIds)
            ->get();

        // Добавляем scores к моделям
        return $chunks->map(function ($chunk) use ($combined) {
            $result = $combined->firstWhere('id', $chunk->id);
            $chunk->similarity = $result['vector_score'] ?? 0;
            $chunk->text_score = $result['text_score'] ?? 0;
            $chunk->score = $result['score'] ?? 0;
            return $chunk;
        })->sortByDesc('score')->values();
    }

    /**
     * Чистый векторный поиск.
     */
    private function vectorSearch(
        string $queryVector,
        int $limit,
        ?array $sourceNumbers = null,
        ?array $categories = null,
    ): array {
        $sql = '
        SELECT
            lc.id,
            1 - (lc.embedding <=> ?::vector) as similarity
        FROM legal_chunks lc
        JOIN legal_sources ls ON lc.legal_source_id = ls.id
        WHERE lc.embedding IS NOT NULL
    ';

        $bindings = [$queryVector];

        if ($sourceNumbers !== null && ! empty($sourceNumbers)) {
            $placeholders = implode(',', array_fill(0, count($sourceNumbers), '?'));
            $sql .= " AND ls.number IN ({$placeholders})";
            $bindings = array_merge($bindings, $sourceNumbers);
        }

        if ($categories !== null && ! empty($categories)) {
            $categoryConditions = [];
            foreach ($categories as $category) {
                $categoryConditions[] = "lc.metadata_json->>'category' = ?";
                $bindings[] = $category;
            }
            $sql .= ' AND (' . implode(' OR ', $categoryConditions) . ')';
        }

        $sql .= ' ORDER BY lc.embedding <=> ?::vector LIMIT ?';
        $bindings[] = $queryVector;
        $bindings[] = $limit;

        return DB::connection('pgsql_knowledge')->select($sql, $bindings);
    }

    /**
     * Текстовый поиск с boosting по заголовку.
     */
    private function textSearch(
        array $keywords,
        int $limit,
        ?array $sourceNumbers = null,
        ?array $categories = null,
    ): array {
        if (empty($keywords)) {
            return [];
        }

        // Формируем выражение для расчёта text_score
        $titleMatches = array_map(function ($kw) {
            return "CASE WHEN LOWER(lc.title) LIKE LOWER(?) THEN 1 ELSE 0 END";
        }, $keywords);

        $contentMatches = array_map(function ($kw) {
            return "CASE WHEN LOWER(lc.content) LIKE LOWER(?) THEN 1 ELSE 0 END";
        }, $keywords);

        $scoreExpr = '(3 * (' . implode(' + ', $titleMatches) . ') + (' . implode(' + ', $contentMatches) . '))';

        // Bindings: сначала для title, потом для content
        $bindings = array_map(fn ($kw) => "%{$kw}%", $keywords);
        $bindings = array_merge($bindings, array_map(fn ($kw) => "%{$kw}%", $keywords));

        $whereClauses = ['1=1'];

        if ($sourceNumbers !== null && ! empty($sourceNumbers)) {
            $placeholders = implode(',', array_fill(0, count($sourceNumbers), '?'));
            $whereClauses[] = "ls.number IN ({$placeholders})";
            $bindings = array_merge($bindings, $sourceNumbers);
        }

        if ($categories !== null && ! empty($categories)) {
            $categoryConditions = [];
            foreach ($categories as $category) {
                $categoryConditions[] = "lc.metadata_json->>'category' = ?";
                $bindings[] = $category;
            }
            $whereClauses[] = '(' . implode(' OR ', $categoryConditions) . ')';
        }

        $whereSql = implode(' AND ', $whereClauses);

        // Используем подзапрос, чтобы можно было фильтровать по алиасу text_score
        $sql = "
        SELECT sub.id, sub.text_score
        FROM (
            SELECT
                lc.id,
                {$scoreExpr} as text_score
            FROM legal_chunks lc
            JOIN legal_sources ls ON lc.legal_source_id = ls.id
            WHERE {$whereSql}
        ) sub
        WHERE sub.text_score > 0
        ORDER BY sub.text_score DESC
        LIMIT ?
    ";

        $bindings[] = $limit;

        return DB::connection('pgsql_knowledge')->select($sql, $bindings);
    }

    /**
     * Объединяет результаты vector и text search.
     */
    private function combineResults(
        array $vectorResults,
        array $textResults,
        float $vectorWeight,
        float $textWeight,
    ): Collection {
        $combined = [];

        // Нормализуем vector scores (уже в диапазоне 0-1)
        foreach ($vectorResults as $result) {
            $id = $result->id;
            if (! isset($combined[$id])) {
                $combined[$id] = [
                    'id' => $id,
                    'vector_score' => 0,
                    'text_score' => 0,
                ];
            }
            $combined[$id]['vector_score'] = max(0, $result->similarity);
        }

        // Нормализуем text scores (максимум может быть разным)
        $maxTextScore = 1;
        foreach ($textResults as $result) {
            $maxTextScore = max($maxTextScore, $result->text_score);
        }

        foreach ($textResults as $result) {
            $id = $result->id;
            if (! isset($combined[$id])) {
                $combined[$id] = [
                    'id' => $id,
                    'vector_score' => 0,
                    'text_score' => 0,
                ];
            }
            $combined[$id]['text_score'] = $result->text_score / $maxTextScore;
        }

        // Вычисляем итоговый score
        foreach ($combined as &$item) {
            $item['score'] = ($item['vector_score'] * $vectorWeight) + ($item['text_score'] * $textWeight);
        }

        return collect($combined);
    }

    /**
     * Извлекает ключевые слова из запроса.
     */
    private function extractKeywords(string $query): array
    {
        // Стоп-слова для русского языка
        $stopWords = [
            'и', 'в', 'на', 'с', 'по', 'для', 'от', 'до', 'из', 'к', 'о', 'об',
            'а', 'но', 'да', 'не', 'ни', 'же', 'ли', 'бы', 'то', 'что', 'как',
            'это', 'тот', 'та', 'то', 'такой', 'какой', 'который', 'когда', 'где',
            'кто', 'чем', 'чего', 'кому', 'чему', 'кем', 'чём', 'при', 'про',
            'какие', 'какой', 'какая', 'какое', 'каком', 'который', 'которая',
            'которые', 'которое', 'которого', 'которой', 'которых', 'которым',
            'данных', 'данные', 'данными', 'является', 'являются', 'может', 'могут',
            'быть', 'должен', 'должна', 'должно', 'должны', 'будет', 'будут',
        ];

        // Разбиваем на слова
        $words = preg_split('/[\s,\.\!\?\;\:\(\)\[\]\{\}]+/u', mb_strtolower($query));

        // Фильтруем стоп-слова и короткие слова
        $keywords = array_filter($words, function ($word) use ($stopWords) {
            $word = trim($word);
            return mb_strlen($word) >= 3 && ! in_array($word, $stopWords);
        });

        return array_values(array_unique($keywords));
    }

    /**
     * Форматирует результаты поиска для LLM.
     */
    public function formatForLLM(Collection $chunks, int $maxTokens = 4000): string
    {
        $formatted = [];
        $totalTokens = 0;

        foreach ($chunks as $chunk) {
            $reference = $chunk->getReference();
            $content = trim($chunk->content);

            // Оценка токенов (примерно 4 символа на токен для русского)
            $estimatedTokens = (int) ceil(mb_strlen($content) / 4);

            if ($totalTokens + $estimatedTokens > $maxTokens) {
                break;
            }

            $formatted[] = "### {$reference}\n{$content}";
            $totalTokens += $estimatedTokens;
        }

        return implode("\n\n---\n\n", $formatted);
    }

    /**
     * Получает статистику по базе знаний.
     */
    public function getStats(): array
    {
        return [
            'sources_count' => LegalSource::query()->where('is_active', true)->count(),
            'chunks_count' => LegalChunk::query()->count(),
            'chunks_with_embedding' => LegalChunk::query()->whereNotNull('embedding')->count(),
            'by_source' => LegalSource::query()
                ->withCount('chunks')
                ->where('is_active', true)
                ->orderBy('number')
                ->get()
                ->map(fn ($s) => [
                    'number' => $s->number,
                    'title' => $s->title,
                    'type' => $s->source_type,
                    'chunks' => $s->chunks_count,
                ])
                ->toArray(),
        ];
    }
    /**
     * Поиск с контекстом организации.
     */
    public function searchWithContext(
        string $query,
        array $organizationProfile,
        int $limit = 15,
    ): Collection {
        // Определяем релевантные категории на основе профиля
        $categories = $this->getRelevantCategories($organizationProfile);

        // Обогащаем запрос контекстом
        $enrichedQuery = $this->enrichQuery($query, $organizationProfile);

        // Используем hybrid search
        return $this->hybridSearch(
            query: $enrichedQuery,
            limit: $limit,
            categories: empty($categories) ? null : $categories,
            vectorWeight: 0.6,
            textWeight: 0.4,
        );
    }

    /**
     * Определяет релевантные категории НПА на основе профиля организации.
     */
    private function getRelevantCategories(array $profile): array
    {
        $categories = [];

        // Если обрабатывает ПДн — добавляем
        if (! empty($profile['processes_personal_data'])) {
            $categories[] = 'personal_data';
        }

        // Если есть КИИ — добавляем
        if (! empty($profile['has_kii']) || ! empty($profile['has_asutp'])) {
            $categories[] = 'critical_infrastructure';
        }

        // Если есть ГИС — добавляем
        if (! empty($profile['has_gis'])) {
            $categories[] = 'government_systems';
        }

        // Если профиль пустой — ищем по всем категориям
        if (empty($categories)) {
            return [];
        }

        return array_unique($categories);
    }

    /**
     * Обогащает запрос контекстом организации для лучшего поиска.
     */
    private function enrichQuery(string $query, array $profile): string
    {
        $context = [];

        if (! empty($profile['processes_personal_data'])) {
            $context[] = 'обработка персональных данных';
        }

        if (! empty($profile['has_kii'])) {
            $context[] = 'критическая информационная инфраструктура';
        }

        if (! empty($profile['has_gis'])) {
            $context[] = 'государственная информационная система';
        }

        if (! empty($profile['has_cross_border_transfer'])) {
            $context[] = 'трансграничная передача';
        }

        if (! empty($profile['data_categories'])) {
            $categoryLabels = [
                'employees' => 'сотрудники',
                'clients' => 'клиенты',
                'patients' => 'пациенты',
                'students' => 'студенты',
                'children' => 'дети',
            ];

            foreach ($profile['data_categories'] as $cat) {
                if (isset($categoryLabels[$cat])) {
                    $context[] = $categoryLabels[$cat];
                }
            }
        }

        if (empty($context)) {
            return $query;
        }

        return $query . ' ' . implode(' ', array_slice($context, 0, 5));
    }
}
