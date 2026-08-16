<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_core';

    public function up(): void
    {
        Schema::connection($this->connection)->create('issue_comments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('document_issue_id')
                ->constrained('document_issues')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('user_id');

            $table->text('content');

            $table->timestamps();

            $table->index('document_issue_id');
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('issue_comments');
    }
};
