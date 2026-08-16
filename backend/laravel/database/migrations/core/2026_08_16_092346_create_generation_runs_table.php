<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_core';

    public function up(): void
    {
        Schema::connection($this->connection)->create('generation_runs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('organization_id');

            $table->foreignId('document_template_id')
                ->constrained('document_templates')
                ->cascadeOnDelete();

            // pending, processing, completed, failed
            $table->string('status')->default('pending');

            // ID созданного документа (после успешной генерации)
            $table->unsignedBigInteger('document_id')->nullable();

            // Ошибка, если генерация не удалась
            $table->text('error_message')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->timestamps();

            $table->index('organization_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('generation_runs');
    }
};
