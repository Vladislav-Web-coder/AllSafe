<?php

namespace App\Domain\Notifications\Entities;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    protected $connection = 'pgsql_core';

    protected $table = 'notification_settings';

    protected $fillable = [
        'user_id',
        'organization_id',
        'email_notifications',
        'browser_notifications',
        'notify_analysis_complete',
        'notify_analysis_failed',
        'notify_task_overdue',
        'notify_task_assigned',
        'notify_issue_added',
        'notify_issue_status_changed',
        'notify_invitation',
        'notify_document_generated',
    ];

    protected function casts(): array
    {
        return [
            'email_notifications' => 'boolean',
            'browser_notifications' => 'boolean',
            'notify_analysis_complete' => 'boolean',
            'notify_analysis_failed' => 'boolean',
            'notify_task_overdue' => 'boolean',
            'notify_task_assigned' => 'boolean',
            'notify_issue_added' => 'boolean',
            'notify_issue_status_changed' => 'boolean',
            'notify_invitation' => 'boolean',
            'notify_document_generated' => 'boolean',
        ];
    }
}
