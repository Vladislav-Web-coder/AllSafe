<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_core';

    public function up(): void
    {
        Schema::connection($this->connection)->create('documents', function (Blueprint $table) {
            $table->id();

            // Организация находится в identity_db,
            // поэтому внешний ключ не создаём.
            $table->unsignedBigInteger('organization_id');

            $table->foreignId('document_type_id')
                ->constrained('document_types')
                ->restrictOnDelete();

            $table->string('title');

            // draft, uploaded, queued, processing, analyzing, completed, failed, archived
            $table->string('status')->default('draft');

            // upload, generated, imported, fixed, manual
            $table->string('source')->default('manual');

            // Позже свяжем с document_versions.
            $table->unsignedBigInteger('current_version_id')->nullable();

            // Пользователи находятся в identity_db,
            // поэтому FK не создаём.
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->jsonb('metadata_json')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'document_type_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('documents');
    }
};
