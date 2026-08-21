<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_core';

    public function up(): void
    {
        Schema::connection($this->connection)->create('golden_examples', function (Blueprint $table) {
            $table->id();

            // Категория примера
            $table->string('category'); // analysis, generation

            // Тип документа
            $table->string('document_type_code')->nullable();

            // Входные данные
            $table->text('input_document');
            $table->jsonb('organization_profile_json')->nullable();
            $table->jsonb('rag_context_json')->nullable();

            // Эталонный вывод (размеченный экспертом)
            $table->jsonb('expected_output_json');

            // Метаданные
            $table->string('difficulty')->default('medium'); // easy, medium, hard
            $table->string('annotated_by');
            $table->timestamp('annotated_at');
            $table->boolean('is_verified')->default(false);
            $table->string('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();

            // Оценка качества
            $table->integer('quality_score')->nullable(); // 1-5
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('category');
            $table->index('document_type_code');
            $table->index('is_verified');
            $table->index('difficulty');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('golden_examples');
    }
};
