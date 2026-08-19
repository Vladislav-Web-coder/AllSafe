<?php

namespace App\Domain\Notifications\Services;

use App\Domain\Notifications\Entities\NotificationSetting;
use App\Domain\Notifications\Repositories\NotificationRepositoryInterface;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function __construct(
        private NotificationRepositoryInterface $notifications,
    ) {}

    /**
     * Отправляет уведомление пользователю.
     */
    public function notify(
        int $userId,
        int $organizationId,
        string $type,
        string $title,
        string $message,
        ?string $linkType = null,
        ?int $linkId = null,
    ): void {
        try {
            // Проверяем настройки пользователя
            if (! $this->isNotificationEnabled($userId, $organizationId, $type)) {
                return;
            }

            $this->notifications->create([
                'organization_id' => $organizationId,
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'link_type' => $linkType,
                'link_id' => $linkId,
            ]);
        } catch (\Throwable $e) {
            Log::error('NotificationService: failed to create notification', [
                'user_id' => $userId,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Уведомляет всех участников организации.
     */
    public function notifyOrganization(
        int $organizationId,
        string $type,
        string $title,
        string $message,
        ?string $linkType = null,
        ?int $linkId = null,
        ?int $excludeUserId = null,
    ): void {
        try {
            $members = \Illuminate\Support\Facades\DB::connection('pgsql_identity')
                ->table('organization_user')
                ->where('organization_id', $organizationId)
                ->pluck('user_id');

            foreach ($members as $userId) {
                if ($excludeUserId && $userId === $excludeUserId) {
                    continue;
                }

                $this->notify(
                    userId: $userId,
                    organizationId: $organizationId,
                    type: $type,
                    title: $title,
                    message: $message,
                    linkType: $linkType,
                    linkId: $linkId,
                );
            }
        } catch (\Throwable $e) {
            Log::error('NotificationService: failed to notify organization', [
                'organization_id' => $organizationId,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Уведомляет пользователей с определёнными ролями.
     */
    public function notifyByRoles(
        int $organizationId,
        array $roles,
        string $type,
        string $title,
        string $message,
        ?string $linkType = null,
        ?int $linkId = null,
    ): void {
        try {
            $members = \Illuminate\Support\Facades\DB::connection('pgsql_identity')
                ->table('organization_user')
                ->where('organization_id', $organizationId)
                ->whereIn('role', $roles)
                ->pluck('user_id');

            foreach ($members as $userId) {
                $this->notify(
                    userId: $userId,
                    organizationId: $organizationId,
                    type: $type,
                    title: $title,
                    message: $message,
                    linkType: $linkType,
                    linkId: $linkId,
                );
            }
        } catch (\Throwable $e) {
            Log::error('NotificationService: failed to notify by roles', [
                'organization_id' => $organizationId,
                'roles' => $roles,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Проверяет, включён ли тип уведомления для пользователя.
     */
    private function isNotificationEnabled(int $userId, int $organizationId, string $type): bool
    {
        $settings = NotificationSetting::query()
            ->where('user_id', $userId)
            ->where('organization_id', $organizationId)
            ->first();

        if (! $settings) {
            // По умолчанию все уведомления включены
            return true;
        }

        return match ($type) {
            'analysis_completed' => $settings->notify_analysis_complete,
            'analysis_failed' => $settings->notify_analysis_failed,
            'task_overdue' => $settings->notify_task_overdue,
            'task_assigned' => $settings->notify_task_assigned,
            'issue_added' => $settings->notify_issue_added,
            'issue_status_changed' => $settings->notify_issue_status_changed,
            'invitation' => $settings->notify_invitation,
            'document_generated' => $settings->notify_document_generated,
            default => true,
        };
    }
}
