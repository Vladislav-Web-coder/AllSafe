<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_core';

    public function up(): void
    {
        Schema::connection($this->connection)->create('document_analysis_runs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('document_id')
                ->constrained('documents')
                ->cascadeOnDelete();

            $table->foreignId('document_version_id')
                ->nullable()
                ->constrained('document_versions')
                ->nullOnDelete();

            $table->unsignedBigInteger('organization_id');

            // pending, processing, completed, failed, cancelled
            $table->string('status')->default('pending');

            $table->unsignedTinyInteger('score')->nullable();

            $table->jsonb('summary_json')->nullable();
            $table->jsonb('missing_sections_json')->nullable();
            $table->jsonb('legal_references_json')->nullable();

            $table->string('model_provider')->nullable();
            $table->string('model_name')->nullable();
            $table->string('prompt_version')->nullable();
            $table->string('knowledge_base_version')->nullable();
            $table->string('requirements_version')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->text('error_message')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->index(['document_id', 'created_at']);
            $table->index(['organization_id', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('document_analysis_runs');
    }
};
