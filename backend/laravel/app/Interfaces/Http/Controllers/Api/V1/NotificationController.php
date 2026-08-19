<?php

namespace App\Interfaces\Http\Controllers\Api\V1;

use App\Domain\Notifications\Entities\NotificationSetting;
use App\Domain\Notifications\Repositories\NotificationRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationRepositoryInterface $notifications,
    ) {}

    /**
     * Список уведомлений пользователя.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $organizationId = $request->query('organization_id')
            ? (int) $request->query('organization_id')
            : null;

        $unreadOnly = $request->query('unread_only') === 'true';
        $perPage = min((int) $request->query('per_page', 20), 50);

        $notifications = $this->notifications->listForUser(
            userId: $user->id,
            organizationId: $organizationId,
            unreadOnly: $unreadOnly,
            perPage: $perPage,
        );

        return response()->json($notifications);
    }

    /**
     * Количество непрочитанных.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        $organizationId = $request->query('organization_id')
            ? (int) $request->query('organization_id')
            : null;

        $count = $this->notifications->countUnread(
            userId: $user->id,
            organizationId: $organizationId,
        );

        return response()->json([
            'count' => $count,
        ]);
    }

    /**
     * Пометить уведомление как прочитанное.
     */
    public function markAsRead(Request $request, int $notificationId): JsonResponse
    {
        $user = $request->user();

        $notification = $this->notifications->findById($notificationId);

        if (! $notification || $notification->user_id !== $user->id) {
            abort(404, 'Уведомление не найдено.');
        }

        $this->notifications->markAsRead($notification);

        return response()->json([
            'message' => 'Уведомление помечено как прочитанное.',
        ]);
    }

    /**
     * Пометить все как прочитанные.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $organizationId = $request->query('organization_id')
            ? (int) $request->query('organization_id')
            : null;

        $count = $this->notifications->markAllAsRead(
            userId: $user->id,
            organizationId: $organizationId,
        );

        return response()->json([
            'message' => "Помечено как прочитанные: {$count}.",
            'count' => $count,
        ]);
    }

    /**
     * Удалить уведомление.
     */
    public function destroy(Request $request, int $notificationId): JsonResponse
    {
        $user = $request->user();

        $notification = $this->notifications->findById($notificationId);

        if (! $notification || $notification->user_id !== $user->id) {
            abort(404, 'Уведомление не найдено.');
        }

        $this->notifications->delete($notification);

        return response()->json([
            'message' => 'Уведомление удалено.',
        ]);
    }

    /**
     * Удалить все уведомления.
     */
    public function destroyAll(Request $request): JsonResponse
    {
        $user = $request->user();
        $organizationId = $request->query('organization_id')
            ? (int) $request->query('organization_id')
            : null;

        $count = $this->notifications->deleteAllForUser(
            userId: $user->id,
            organizationId: $organizationId,
        );

        return response()->json([
            'message' => "Удалено уведомлений: {$count}.",
            'count' => $count,
        ]);
    }

    /**
     * Получить настройки уведомлений.
     */
    public function getSettings(Request $request): JsonResponse
    {
        $user = $request->user();
        $organizationId = (int) $request->query('organization_id');

        $settings = NotificationSetting::query()
            ->where('user_id', $user->id)
            ->where('organization_id', $organizationId)
            ->first();

        if (! $settings) {
            // Создаём настройки по умолчанию
            $settings = NotificationSetting::create([
                'user_id' => $user->id,
                'organization_id' => $organizationId,
            ]);
        }

        return response()->json([
            'data' => $settings,
        ]);
    }

    /**
     * Обновить настройки уведомлений.
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'organization_id' => ['required', 'integer'],
            'email_notifications' => ['boolean'],
            'browser_notifications' => ['boolean'],
            'notify_analysis_complete' => ['boolean'],
            'notify_analysis_failed' => ['boolean'],
            'notify_task_overdue' => ['boolean'],
            'notify_task_assigned' => ['boolean'],
            'notify_issue_added' => ['boolean'],
            'notify_issue_status_changed' => ['boolean'],
            'notify_invitation' => ['boolean'],
            'notify_document_generated' => ['boolean'],
        ]);

        $settings = NotificationSetting::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'organization_id' => $validated['organization_id'],
            ],
            $validated
        );

        return response()->json([
            'message' => 'Настройки сохранены.',
            'data' => $settings,
        ]);
    }
}
