<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_knowledge';

    public function up(): void
    {
        Schema::connection($this->connection)->create('legal_chunks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('legal_source_id')
                ->constrained('legal_sources')
                ->cascadeOnDelete();

            $table->unsignedInteger('chunk_index');

            // Статья, часть, пункт, если применимо
            $table->string('article')->nullable();
            $table->string('part')->nullable();
            $table->string('clause')->nullable();

            $table->string('title')->nullable();
            $table->text('content');

            $table->jsonb('metadata_json')->nullable();

            // Вектор для pgvector (размерность зависит от embedding-модели)
            // Для sentence-transformers/all-MiniLM-L6-v2 размерность 384
            // Для более крупных моделей может быть 768, 1024, 1536

            $table->date('actual_as_of')->nullable();

            $table->timestamps();

            $table->index('legal_source_id');
            $table->index('article');
        });

        // Добавляем колонку embedding через raw SQL для pgvector
        DB::connection('pgsql_knowledge')->statement('
            ALTER TABLE legal_chunks
            ADD COLUMN embedding vector(384)
        ');

        // Создаём индекс для векторного поиска
        DB::connection('pgsql_knowledge')->statement('
            CREATE INDEX legal_chunks_embedding_idx
            ON legal_chunks
            USING ivfflat (embedding vector_cosine_ops)
            WITH (lists = 100)
        ');
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('legal_chunks');
    }
};
