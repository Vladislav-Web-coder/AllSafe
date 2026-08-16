<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_identity';

    public function up(): void
    {
        Schema::connection($this->connection)->create('organization_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')
                ->unique()
                ->constrained('organizations')
                ->cascadeOnDelete();

            // Флаги
            $table->boolean('processes_personal_data')->default(false);
            $table->boolean('has_website')->default(false);
            $table->boolean('has_gis')->default(false);
            $table->boolean('has_kii')->default(false);
            $table->boolean('has_asutp')->default(false);
            $table->boolean('uses_cloud')->default(false);
            $table->boolean('has_contractors')->default(false);
            $table->boolean('has_cross_border_transfer')->default(false);

            // Категории данных
            // Пример: ["employees", "clients", "patients", "children"]
            $table->jsonb('data_categories')->nullable();

            // Специальные категории ПДн
            // Пример: ["health", "biometric", "criminal"]
            $table->jsonb('special_data_categories')->nullable();

            // Количество субъектов ПДн
            $table->unsignedInteger('subjects_count')->nullable();

            // Уровень защищённости (УЗ-1, УЗ-2, УЗ-3, УЗ-4)
            $table->string('protection_level')->nullable();

            // Дополнительные атрибуты
            $table->jsonb('extra_attributes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('organization_profiles');
    }
};
