<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'pgsql_knowledge';

    public function up(): void
    {
        DB::connection($this->connection)->statement('CREATE EXTENSION IF NOT EXISTS vector');
    }

    public function down(): void
    {
        DB::connection($this->connection)->statement('DROP EXTENSION IF EXISTS vector');
    }
};
