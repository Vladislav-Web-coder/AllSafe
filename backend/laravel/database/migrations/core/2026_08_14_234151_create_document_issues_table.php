<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_core';

    public function up(): void
    {
        Schema::connection($this->connection)->create('document_issues', function (Blueprint $table) {
            $table->id();

            $table->foreignId('analysis_run_id')
                ->constrained('document_analysis_runs')
                ->cascadeOnDelete();

            $table->foreignId('document_id')
                ->constrained('documents')
                ->cascadeOnDelete();

            $table->foreignId('document_version_id')
                ->nullable()
                ->constrained('document_versions')
                ->nullOnDelete();

            $table->unsignedBigInteger('organization_id');

            $table->string('requirement_code')->nullable();

            // critical, high, medium, low, info
            $table->string('severity')->default('info');

            $table->string('title');
            $table->text('description')->nullable();
            $table->text('recommendation')->nullable();

            $table->jsonb('legal_basis_json')->nullable();

            $table->string('section_code')->nullable();

            // open, accepted, fixed, rejected, deferred
            $table->string('status')->default('open');

            $table->text('user_comment')->nullable();

            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            $table->index(['analysis_run_id', 'status']);
            $table->index(['document_id', 'status']);
            $table->index(['organization_id', 'status']);
            $table->index('severity');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('document_issues');
    }
};
