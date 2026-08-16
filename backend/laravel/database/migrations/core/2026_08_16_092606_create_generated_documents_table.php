<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_core';

    public function up(): void
    {
        Schema::connection($this->connection)->create('generated_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('generation_run_id')
                ->constrained('generation_runs')
                ->cascadeOnDelete();

            // Сгенерированный контент в формате Markdown/HTML
            $table->text('content');

            // Структура разделов
            $table->jsonb('sections_json')->nullable();

            // Метаданные генерации
            $table->jsonb('metadata_json')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('generated_documents');
    }
};
