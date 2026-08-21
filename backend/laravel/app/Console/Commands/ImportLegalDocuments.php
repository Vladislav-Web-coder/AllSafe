<?php

namespace App\Console\Commands;

use App\Domain\Knowledge\Entities\LegalChunk;
use App\Domain\Knowledge\Entities\LegalSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser as PdfParser;

class ImportLegalDocuments extends Command
{
    protected $signature = 'knowledge:import-documents
                            {--path= : Путь к директории с файлами НПА (.pdf, .txt)}
                            {--clear : Очистить базу перед импортом}
                            {--source-id= : Переимпортировать только конкретный источник}';

    protected $description = 'Импортирует нормативные документы из PDF/TXT файлов с разбивкой на главы, статьи, части, пункты';

    /**
     * Реестр известных документов.
     * Если в файле нет YAML-заголовка — используются эти метаданные.
     */
    private array $registry = [
        '152-FZ' => [
            'source_type' => 'federal_law',
            'title' => 'О персональных данных',
            'number' => '152-ФЗ',
            'published_at' => '2006-07-27',
            'source_url' => 'http://pravo.gov.ru/proxy/ips/?docbody=&nd=102108261',
            'category' => 'personal_data',
        ],
        '187-FZ' => [
            'source_type' => 'federal_law',
            'title' => 'О безопасности критической информационной инфраструктуры Российской Федерации',
            'number' => '187-ФЗ',
            'published_at' => '2017-07-26',
            'source_url' => 'http://pravo.gov.ru/proxy/ips/?docbody=&nd=102439340',
            'category' => 'critical_infrastructure',
        ],
        'FSTEC-21' => [
            'source_type' => 'order',
            'title' => 'Состав и содержание организационных и технических мер по обеспечению безопасности ПДн',
            'number' => '21',
            'published_at' => '2013-02-18',
            'source_url' => 'https://fstec.ru/dokumenty/vse-dokumenty/prikazy/prikaz-fstek-rossii-ot-18-fevralya-2013-g-n-21',
            'category' => 'personal_data',
            'metadata' => ['authority' => 'ФСТЭК России', 'short_name' => 'Приказ ФСТЭК № 21'],
        ],
        'FSTEC-17' => [
            'source_type' => 'order',
            'title' => 'Требования о защите информации, не составляющей государственную тайну',
            'number' => '17',
            'published_at' => '2013-02-11',
            'source_url' => 'https://fstec.ru/dokumenty/vse-dokumenty/prikazy/prikaz-fstek-rossii-ot-11-fevralya-2013-g-n-17',
            'category' => 'government_systems',
            'metadata' => ['authority' => 'ФСТЭК России', 'short_name' => 'Приказ ФСТЭК № 17'],
        ],
        'PP-1119' => [
            'source_type' => 'government_resolution',
            'title' => 'Требования к защите персональных данных при их обработке в ИСПДн',
            'number' => '1119',
            'published_at' => '2012-11-01',
            'source_url' => 'https://www.consultant.ru/document/cons_doc_LAW_137356/',
            'category' => 'personal_data',
            'metadata' => ['authority' => 'Правительство РФ', 'short_name' => 'ПП РФ № 1119'],
        ],
    ];

    public function handle(): int
    {
        $path = $this->option('path') ?? storage_path('app/legal_documents');
        $clear = $this->option('clear');
        $sourceId = $this->option('source-id');

        if (! File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
            $this->info("Создана директория: {$path}");
            $this->warn("Поместите туда PDF или TXT файлы НПА и повторите запуск.");
            return self::FAILURE;
        }

        $files = collect(File::files($path))
            ->filter(fn ($file) => in_array(strtolower($file->getExtension()), ['pdf', 'txt']));

        if ($files->isEmpty()) {
            $this->warn("В директории {$path} нет PDF/TXT файлов");
            return self::FAILURE;
        }

        $this->info("Найдено файлов: {$files->count()}");

        if ($clear) {
            $this->warn("Очистка базы знаний...");
            DB::connection('pgsql_knowledge')->transaction(function () {
                LegalChunk::query()->delete();
                LegalSource::query()->delete();
            });
        }

        $imported = 0;
        $errors = 0;

        foreach ($files as $file) {
            $basename = $file->getFilenameWithoutExtension();

            if ($sourceId && $sourceId !== $basename) {
                continue;
            }

            try {
                $this->importFile($file);
                $imported++;
                $this->info("✓ Импортирован: {$basename}");
            } catch (\Throwable $e) {
                $errors++;
                $this->error("✗ Ошибка {$basename}: {$e->getMessage()}");
                Log::error('ImportLegalDocuments error', [
                    'file' => $basename,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->newLine();
        $this->info("Импорт завершён. Успешно: {$imported}, Ошибки: {$errors}");
        $this->showStats();

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Импорт одного файла.
     */
    private function importFile(\SplFileInfo $file): void
    {
        $basename = $file->getFilenameWithoutExtension();
        $extension = strtolower($file->getExtension());

        // Извлекаем текст
        $content = match ($extension) {
            'pdf' => $this->extractPdfText($file->getPathname()),
            'txt' => File::get($file->getPathname()),
            default => throw new \RuntimeException("Unsupported extension: {$extension}"),
        };

        if (empty(trim($content))) {
            throw new \RuntimeException('Файл пустой или текст не извлечён');
        }

        // Парсим метаданные
        $metadata = $this->parseMetadata($basename, $content);

        // Очистка контента от мусора (номера страниц, колонтитулы)
        $content = $this->cleanContent($content);

        // Парсим иерархию: главы → статьи → части → пункты
        $chunks = $this->parseHierarchy($content, $metadata['number']);

        DB::connection('pgsql_knowledge')->transaction(function () use ($basename, $metadata, $chunks) {
            // Удаляем старый источник
            LegalSource::query()->where('number', $metadata['number'])->delete();

            $source = LegalSource::create([
                'source_type' => $metadata['source_type'],
                'title' => $metadata['title'],
                'number' => $metadata['number'],
                'published_at' => $metadata['published_at'],
                'actual_as_of' => now(),
                'source_url' => $metadata['source_url'] ?? null,
                'is_active' => true,
                'metadata_json' => array_merge(
                    ['category' => $metadata['category'] ?? 'general', 'filename' => $basename],
                    $metadata['metadata'] ?? []
                ),
            ]);

            $this->info("  Источник ID: {$source->id}, чанков: " . count($chunks));

            foreach ($chunks as $index => $chunk) {
                LegalChunk::create([
                    'legal_source_id' => $source->id,
                    'chunk_index' => $index,
                    'chapter' => $chunk['chapter'] ?? null,
                    'article' => $chunk['article'] ?? null,
                    'part' => $chunk['part'] ?? null,
                    'clause' => $chunk['clause'] ?? null,
                    'title' => $chunk['title'],
                    'content' => $chunk['content'],
                    'path' => $chunk['path'],
                    'metadata_json' => [
                        'source_number' => $metadata['number'],
                        'source_title' => $metadata['title'],
                        'category' => $metadata['category'] ?? 'general',
                    ],
                    'actual_as_of' => now(),
                ]);
            }
        });
    }

    /**
     * Извлечение текста из PDF.
     */
    private function extractPdfText(string $filePath): string
    {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($filePath);

        $pages = [];
        foreach ($pdf->getPages() as $page) {
            $pages[] = $page->getText();
        }

        return implode("\n\n", $pages);
    }

    /**
     * Очистка содержимого от мусора.
     */
    private function cleanContent(string $content): string
    {
        // Убираем множественные пробелы/табы
        $content = preg_replace('/[ \t]+/', ' ', $content);

        // Убираем номера страниц (отдельные строки с 1-3 цифрами)
        $content = preg_replace('/^\s*\d{1,4}\s*$/m', '', $content);

        // Убираем колонтитулы типа "Федеральный закон от ... N 152-ФЗ"
        $content = preg_replace('/^Федеральный закон от [^\n]+N \d+-ФЗ\s*$/m', '', $content);

        // Убираем множественные пустые строки
        $content = preg_replace('/\n{3,}/', "\n\n", $content);

        return trim($content);
    }

    /**
     * Парсит метаданные из YAML-заголовка или определяет по имени файла.
     */
    private function parseMetadata(string $basename, string &$content): array
    {
        $lines = explode("\n", $content);

        // Проверяем наличие YAML-заголовка
        if (! empty($lines) && trim($lines[0]) === '---') {
            array_shift($lines);
            $yamlLines = [];

            while (! empty($lines) && trim($lines[0]) !== '---') {
                $yamlLines[] = array_shift($lines);
            }
            if (! empty($lines)) {
                array_shift($lines); // убираем второй ---
            }

            $metadata = [];
            foreach ($yamlLines as $line) {
                if (preg_match('/^(\w+):\s*(.+)$/', trim($line), $matches)) {
                    $metadata[$matches[1]] = trim($matches[2], '"\'');
                }
            }

            $content = implode("\n", $lines);

            return $metadata;
        }

        // Ищем в реестре
        $key = strtoupper($basename);
        if (isset($this->registry[$key])) {
            return $this->registry[$key];
        }

        // По умолчанию
        return [
            'source_type' => 'unknown',
            'title' => $basename,
            'number' => strtoupper($basename),
            'published_at' => null,
            'category' => 'general',
        ];
    }

    /**
     * Основной парсер иерархии.
     *
     * Иерархия: Глава → Статья → Часть → Пункт → Подпункт
     * Каждый чанк — это либо одна статья (с её частями и пунктами),
     * либо одна глава без статей (общие положения).
     */
    private function parseHierarchy(string $content, string $sourceNumber): array
    {
        $lines = explode("\n", $content);

        // Состояние парсера
        $state = [
            'current_chapter' => null,        // "1", "2" и т.д.
            'current_chapter_title' => null,  // "ОБЩИЕ ПОЛОЖЕНИЯ"
            'current_article' => null,        // "1", "5", "10.1"
            'current_article_title' => null,  // "Сфера действия настоящего Федерального закона"
            'current_part' => null,           // "1", "1.1", "2"
            'buffer' => [],                   // накопленные строки
            'chunks' => [],                   // итоговые чанки
        ];

        // Регулярки
        $chapterRegex = '/^Глава\s+([\dIVXLCDM]+(?:\.\d+)?)\.?\s*(.*)$/ui';
        $sectionRegex = '/^(Раздел|Приложение)\s+([\dIVXLCDM]+(?:\.\d+)?)\.?\s*(.*)$/ui';
        $articleRegex = '/^Статья\s+(\d+(?:\.\d+)?)\.?\s*(.*)$/ui';
        $partRegex = '/^(\d+(?:\.\d+)?)\.\s+(.+)$/u';
        $clauseRegex = '/^\s*(\d+(?:\.\d+)?)\)\s+(.+)$/u';
        $subclauseRegex = '/^\s*([а-яa-z])\)\s+(.+)$/ui';

        $articleChangeRegex = '/^\(в ред\. Федерального закона/i';
        $introducedRegex = '/^\(п\. .+ введен/i';
        $lostForceRegex = '/утратил(а|о)? силу/i';

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                if (! empty($state['buffer'])) {
                    $state['buffer'][] = '';
                }
                continue;
            }

            // === Глава ===
            if (preg_match($chapterRegex, $trimmed, $matches)) {
                $this->flushBuffer($state);

                $state['current_chapter'] = $matches[1];
                $state['current_chapter_title'] = trim($matches[2]);
                $state['current_article'] = null;
                $state['current_article_title'] = null;
                $state['current_part'] = null;
                $state['buffer'][] = $trimmed;
                continue;
            }

            // === Раздел/Приложение ===
            if (preg_match($sectionRegex, $trimmed, $matches)) {
                $this->flushBuffer($state);

                $state['current_chapter'] = null; // сбрасываем главу
                $state['current_chapter_title'] = "{$matches[1]} {$matches[2]}";
                $state['current_article'] = null;
                $state['current_part'] = null;
                $state['buffer'][] = $trimmed;
                continue;
            }

            // === Статья ===
            if (preg_match($articleRegex, $trimmed, $matches)) {
                $this->flushBuffer($state);

                $state['current_article'] = $matches[1];
                $state['current_article_title'] = trim($matches[2]);
                $state['current_part'] = null;
                $state['buffer'][] = $trimmed;
                continue;
            }

            // === Часть статьи: "1. текст" или "1.1. текст" ===
            if ($state['current_article'] !== null && preg_match($partRegex, $trimmed, $matches)) {
                // Создаём отдельный чанк для каждой части
                $this->flushBuffer($state);

                $state['current_part'] = $matches[1];
                $state['buffer'][] = $trimmed;
                continue;
            }

            // === Пункт: "1) текст" ===
            if (preg_match($clauseRegex, $trimmed, $matches)) {
                // Пункты добавляем в текущий буфер
                $state['buffer'][] = $trimmed;
                continue;
            }

            // === Подпункт: "а) текст" ===
            if (preg_match($subclauseRegex, $trimmed, $matches)) {
                $state['buffer'][] = $trimmed;
                continue;
            }

            // Пропускаем пометки об изменениях на отдельной строке
            if (preg_match($articleChangeRegex, $trimmed) || preg_match($introducedRegex, $trimmed)) {
                // Можно включать, но обычно они мешают — пропускаем
                continue;
            }

            // Обычная строка
            $state['buffer'][] = $trimmed;
        }

        // Последний буфер
        $this->flushBuffer($state);

        // Постобработка
        return $this->postProcessChunks($state['chunks'], $sourceNumber);
    }

    /**
     * Сохраняет накопленный буфер как отдельный чанк.
     */
    private function flushBuffer(array &$state): void
    {
        $content = implode("\n", $state['buffer']);
        $content = trim($content);

        if (empty($content)) {
            $state['buffer'] = [];
            return;
        }

        // Формируем путь цитирования
        $pathParts = [];
        if ($state['current_chapter']) {
            $pathParts[] = "Глава {$state['current_chapter']}";
        }
        if ($state['current_article']) {
            $pathParts[] = "ст. {$state['current_article']}";
        }
        if ($state['current_part']) {
            $pathParts[] = "ч. {$state['current_part']}";
        }

        // Заголовок чанка
        $title = $this->buildChunkTitle($state);

        $state['chunks'][] = [
            'chapter' => $state['current_chapter'],
            'article' => $state['current_article'],
            'part' => $state['current_part'],
            'clause' => null,
            'title' => $title,
            'content' => $content,
            'path' => implode(', ', $pathParts) ?: 'Общие положения',
        ];

        $state['buffer'] = [];
    }

    /**
     * Формирует заголовок чанка для отображения.
     */
    private function buildChunkTitle(array $state): string
    {
        // Специальный случай: преамбула документа
        if (! $state['current_chapter'] && ! $state['current_article']) {
            $firstLine = $state['buffer'][0] ?? '';

            // Если это начало документа — помечаем как преамбулу
            if (empty($firstLine) || mb_strlen($firstLine) < 50) {
                return 'Преамбула';
            }

            return 'Преамбула и общие положения';
        }

        $parts = [];

        if ($state['current_chapter'] && ! $state['current_article']) {
            $parts[] = "Глава {$state['current_chapter']}";
            if ($state['current_chapter_title']) {
                $parts[] = $state['current_chapter_title'];
            }
            return implode('. ', $parts);
        }

        if ($state['current_article']) {
            $parts[] = "Статья {$state['current_article']}";
            if ($state['current_article_title']) {
                $parts[] = $state['current_article_title'];
            }
            if ($state['current_part']) {
                $parts[] = "часть {$state['current_part']}";
            }
        }

        return implode('. ', $parts) ?: 'Общие положения';
    }

    /**
     * Постобработка: разбиение слишком крупных чанков, удаление пустых.
     */
    private function postProcessChunks(array $chunks, string $sourceNumber): array
    {
        $result = [];

        foreach ($chunks as $chunk) {
            // Пропускаем слишком короткие
            if (mb_strlen($chunk['content']) < 50) {
                continue;
            }

            // Если чанк слишком большой — разбиваем по абзацам
            if (mb_strlen($chunk['content']) > 4000) {
                $split = $this->splitByParagraphs($chunk, 3000);
                foreach ($split as $i => $sub) {
                    if ($i > 0) {
                        $sub['title'] .= " (продолжение, часть " . ($i + 1) . ")";
                        $sub['path'] .= " (ч. " . ($i + 1) . ")";
                    }
                    $result[] = $sub;
                }
            } else {
                $result[] = $chunk;
            }
        }

        return $result;
    }

    /**
     * Разбивает чанк на подчанки по абзацам.
     */
    private function splitByParagraphs(array $chunk, int $maxLength): array
    {
        $paragraphs = preg_split('/\n\s*\n/', $chunk['content']);
        $subChunks = [];
        $current = '';

        foreach ($paragraphs as $para) {
            if (mb_strlen($current . $para) > $maxLength && ! empty($current)) {
                $sub = $chunk;
                $sub['content'] = trim($current);
                $subChunks[] = $sub;
                $current = $para;
            } else {
                $current .= (! empty($current) ? "\n\n" : '') . $para;
            }
        }

        if (! empty($current)) {
            $sub = $chunk;
            $sub['content'] = trim($current);
            $subChunks[] = $sub;
        }

        return $subChunks;
    }

    /**
     * Показывает статистику.
     */
    private function showStats(): void
    {
        $this->info("\nСтатистика базы знаний:");

        $sourcesCount = LegalSource::query()->count();
        $chunksCount = LegalChunk::query()->count();

        // Для pgvector используем только whereNotNull, без сравнения со строкой
        $withEmbedding = LegalChunk::query()
            ->whereNotNull('embedding')
            ->count();

        $this->table(
            ['Метрика', 'Значение'],
            [
                ['Источников', $sourcesCount],
                ['Фрагментов', $chunksCount],
                ['С embeddings', $withEmbedding],
                ['Без embeddings', $chunksCount - $withEmbedding],
            ]
        );

        $sourcesList = LegalSource::query()
            ->withCount('chunks')
            ->orderBy('number')
            ->get()
            ->map(fn ($s) => [
                $s->number,
                mb_substr($s->title, 0, 50),
                $s->source_type,
                $s->chunks_count,
            ]);

        if ($sourcesList->isNotEmpty()) {
            $this->newLine();
            $this->table(
                ['Номер', 'Название', 'Тип', 'Чанков'],
                $sourcesList->toArray()
            );
        }
    }
}
