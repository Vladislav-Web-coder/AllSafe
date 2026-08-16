<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_audit';

    public function up(): void
    {
        Schema::connection($this->connection)->create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Кто выполнил действие
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_email')->nullable();

            // В какой организации
            $table->unsignedBigInteger('organization_id')->nullable();

            // Действие
            $table->string('action');

            // Тип сущности: document, issue, task, organization, etc.
            $table->string('subject_type')->nullable();

            // ID сущности
            $table->unsignedBigInteger('subject_id')->nullable();

            // Описание действия
            $table->text('description')->nullable();

            // Старые значения (для update)
            $table->jsonb('old_values')->nullable();

            // Новые значения (для create/update)
            $table->jsonb('new_values')->nullable();

            // Метаданные запроса
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('request_id')->nullable();

            // Результат: success, error
            $table->string('result')->default('success');

            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id');
            $table->index('organization_id');
            $table->index('action');
            $table->index('subject_type');
            $table->index('subject_id');
            $table->index('created_at');
            $table->index(['organization_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('audit_logs');
    }
};
