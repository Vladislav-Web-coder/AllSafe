<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_core';

    public function up(): void
    {
        Schema::connection($this->connection)->create('document_requirement_rules', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();

            $table->foreignId('document_type_id')
                ->constrained('document_types')
                ->cascadeOnDelete();

            // Условие применимости в JSON
            // Пример: {"processes_personal_data": true}
            // Пример: {"has_website": true, "processes_personal_data": true}
            $table->jsonb('condition_json');

            // Приоритет (чем меньше, тем важнее)
            $table->unsignedInteger('priority')->default(100);

            // Обязательность: required, recommended, optional
            $table->string('obligation_level')->default('required');

            // Нормативное основание
            $table->jsonb('legal_basis_json')->nullable();

            // Описание
            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('document_type_id');
            $table->index('is_active');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('document_requirement_rules');
    }
};
