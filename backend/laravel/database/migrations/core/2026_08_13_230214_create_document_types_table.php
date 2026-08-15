<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_core';

    public function up(): void
    {
        Schema::connection($this->connection)->create('document_types', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();
            $table->string('name');
            $table->string('category');

            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('can_be_generated')->default(false);

            // Обязательные разделы документа.
            // Например:
            // ["Цели обработки", "Категории данных", "Ответственное лицо"]
            $table->jsonb('required_sections_json')->nullable();

            // Нормативные основания.
            // Например:
            // ["152-ФЗ", "ПП РФ № 1119", "Приказ ФСТЭК № 21"]
            $table->jsonb('legal_basis_json')->nullable();

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index('category');
            $table->index('is_active');
            $table->index('can_be_generated');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('document_types');
    }
};
