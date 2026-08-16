<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_core';

    public function up(): void
    {
        Schema::connection($this->connection)->create('issue_history', function (Blueprint $table) {
            $table->id();

            $table->foreignId('document_issue_id')
                ->constrained('document_issues')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('user_id')->nullable();

            // Тип изменения: status_changed, comment_added, assigned, etc.
            $table->string('change_type');

            $table->string('field_changed')->nullable();
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();

            $table->text('comment')->nullable();

            $table->timestamps();

            $table->index('document_issue_id');
            $table->index('change_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('issue_history');
    }
};
