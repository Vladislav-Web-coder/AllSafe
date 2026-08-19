<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_core';

    public function up(): void
    {
        Schema::connection($this->connection)->create('notifications', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('user_id');

            $table->string('type');

            $table->string('title');
            $table->text('message');

            // Ссылка на связанный объект
            $table->string('link_type')->nullable();
            $table->unsignedBigInteger('link_id')->nullable();

            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->index('organization_id');
            $table->index('user_id');
            $table->index('type');
            $table->index('read_at');
            $table->index(['user_id', 'read_at']);
            $table->index(['organization_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('notifications');
    }
};
