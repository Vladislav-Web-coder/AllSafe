<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_knowledge';

    public function up(): void
    {
        Schema::connection($this->connection)->create('legal_sources', function (Blueprint $table) {
            $table->id();

            // federal_law, government_decree, fstec_order, fsb_order, methodology, gost
            $table->string('source_type');

            $table->string('title');
            $table->string('number')->nullable();
            $table->date('published_at')->nullable();
            $table->date('actual_as_of')->nullable();

            $table->string('source_url')->nullable();
            $table->boolean('is_active')->default(true);

            $table->jsonb('metadata_json')->nullable();

            $table->timestamps();

            $table->index('source_type');
            $table->index('is_active');
            $table->index('actual_as_of');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('legal_sources');
    }
};
