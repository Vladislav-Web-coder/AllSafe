<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_core';

    public function up(): void
    {
        Schema::connection($this->connection)->create('document_templates', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();

            $table->foreignId('document_type_id')
                ->constrained('document_types')
                ->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            // Промпт для LLM: инструкция по генерации контента
            $table->text('generation_prompt');

            // Обязательные разделы документа
            // Пример: ["Общие положения", "Цели обработки", "Порядок уничтожения"]
            $table->jsonb('required_sections_json');

            // Переменные, которые подставляются из профиля
            // Пример: ["organization_name", "protection_level", "data_categories"]
            $table->jsonb('template_variables_json')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('document_type_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('document_templates');
    }
};
