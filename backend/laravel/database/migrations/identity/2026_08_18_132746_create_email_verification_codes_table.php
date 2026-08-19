<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_identity';

    public function up(): void
    {
        Schema::connection($this->connection)->create('email_verification_codes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('email');

            // Тип: register, change_email, reset_password
            $table->string('purpose');

            $table->string('code');

            $table->boolean('used')->default(false);
            $table->timestamp('expires_at');

            $table->timestamps();

            $table->index('user_id');
            $table->index('email');
            $table->index('purpose');
            $table->index('code');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('email_verification_codes');
    }
};
