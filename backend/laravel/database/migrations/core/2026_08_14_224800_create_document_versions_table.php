<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_core';

    public function up(): void
    {
        Schema::connection($this->connection)->create('document_versions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('document_id')
                ->constrained('documents')
                ->cascadeOnDelete();

            $table->unsignedInteger('version_number');

            // upload, generated, imported, fixed, manual
            $table->string('source')->default('upload');

            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('file_hash')->nullable();

            $table->string('storage_disk')->default('minio');

            // Позже сюда можно сохранять распознанный текст.
            $table->string('parsed_text_path')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();

            $table->jsonb('metadata_json')->nullable();

            $table->timestamps();

            $table->unique(['document_id', 'version_number']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('document_versions');
    }
};
