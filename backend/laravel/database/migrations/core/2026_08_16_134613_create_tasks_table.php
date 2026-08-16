<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_core';

    public function up(): void
    {
        Schema::connection($this->connection)->create('tasks', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('organization_id');

            $table->string('title');
            $table->text('description')->nullable();

            // new, in_progress, blocked, done, cancelled
            $table->string('status')->default('new');

            // low, medium, high, critical
            $table->string('priority')->default('medium');

            // Источник задачи: manual, issue, analysis, generation
            $table->string('source_type')->default('manual');

            // Связь с замечанием (если создана из замечания)
            $table->foreignId('document_issue_id')
                ->nullable()
                ->constrained('document_issues')
                ->nullOnDelete();

            // Связь с документом
            $table->unsignedBigInteger('document_id')->nullable();

            // Назначенный исполнитель (user_id из identity_db)
            $table->unsignedBigInteger('assigned_to')->nullable();

            // Создатель задачи
            $table->unsignedBigInteger('created_by')->nullable();

            // Сроки
            $table->date('due_date')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('organization_id');
            $table->index('status');
            $table->index('priority');
            $table->index('assigned_to');
            $table->index('document_issue_id');
            $table->index('document_id');
            $table->index('due_date');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('tasks');
    }
};
