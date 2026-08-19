<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_identity';

    public function up(): void
    {
        Schema::connection($this->connection)->table('auth_refresh_tokens', function (Blueprint $table) {
            $table->unsignedBigInteger('tokenable_id')->nullable()->after('user_id');
            $table->index('tokenable_id');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('auth_refresh_tokens', function (Blueprint $table) {
            $table->dropColumn('tokenable_id');
        });
    }
};
