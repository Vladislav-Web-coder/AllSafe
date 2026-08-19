<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_core';

    public function up(): void
    {
        Schema::connection($this->connection)->create('notification_settings', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->unique();
            $table->unsignedBigInteger('organization_id');

            $table->boolean('email_notifications')->default(true);
            $table->boolean('browser_notifications')->default(false);

            $table->boolean('notify_analysis_complete')->default(true);
            $table->boolean('notify_analysis_failed')->default(true);
            $table->boolean('notify_task_overdue')->default(true);
            $table->boolean('notify_task_assigned')->default(true);
            $table->boolean('notify_issue_added')->default(true);
            $table->boolean('notify_issue_status_changed')->default(true);
            $table->boolean('notify_invitation')->default(true);
            $table->boolean('notify_document_generated')->default(true);

            $table->timestamps();

            $table->index('user_id');
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('notification_settings');
    }
};
