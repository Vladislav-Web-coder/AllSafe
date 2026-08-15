<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_identity';

    public function up(): void
    {
        Schema::connection($this->connection)->create('organization_types', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);

            // Дополнительная информация для compliance-логики.
            // Например:
            // {
            //   "may_be_pd_operator": true,
            //   "may_have_gis": false,
            //   "may_be_kii_subject": true
            // }
            $table->jsonb('applicability_json')->nullable();

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index('is_active');
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('organization_types');
    }
};
