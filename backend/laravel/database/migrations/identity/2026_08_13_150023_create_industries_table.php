<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_identity';

    public function up(): void
    {
        Schema::connection($this->connection)->create('industries', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();

            // Отрасль может быть релевантна для КИИ.
            $table->boolean('kii_relevant')->default(false);

            $table->boolean('is_active')->default(true);

            // Например:
            // {
            //   "fstec_sector": "healthcare",
            //   "requires_kii_check": true
            // }
            $table->jsonb('applicability_json')->nullable();

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index('is_active');
            $table->index('code');
            $table->index('kii_relevant');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('industries');
    }
};
