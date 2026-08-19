<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_identity';

    public function up(): void
    {
        Schema::connection($this->connection)->create('organization_invitations', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('organization_id');
            $table->string('email');
            $table->string('role')->default('employee');

            $table->string('token')->unique();

            $table->unsignedBigInteger('invited_by')->nullable();

            // pending, accepted, expired, cancelled
            $table->string('status')->default('pending');

            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();

            $table->timestamps();

            $table->index('organization_id');
            $table->index('email');
            $table->index('token');
            $table->index('status');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('organization_invitations');
    }
};
