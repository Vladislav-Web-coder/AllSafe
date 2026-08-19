<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Notifications\Entities\Notification;
use App\Domain\Notifications\Repositories\NotificationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentNotificationRepository implements NotificationRepositoryInterface
{
    public function create(array $data): Notification
    {
        return Notification::query()->create($data);
    }

    public function findById(int $id): ?Notification
    {
        return Notification::query()->find($id);
    }

    public function listForUser(
        int $userId,
        ?int $organizationId = null,
        bool $unreadOnly = false,
        int $perPage = 20,
    ): LengthAwarePaginator {
        $query = Notification::query()
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        if ($organizationId !== null) {
            $query->where('organization_id', $organizationId);
        }

        if ($unreadOnly) {
            $query->whereNull('read_at');
        }

        return $query->paginate($perPage);
    }

    public function markAsRead(Notification $notification): void
    {
        $notification->markAsRead();
    }

    public function markAllAsRead(int $userId, ?int $organizationId = null): int
    {
        $query = Notification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at');

        if ($organizationId !== null) {
            $query->where('organization_id', $organizationId);
        }

        return $query->update(['read_at' => now()]);
    }

    public function delete(Notification $notification): void
    {
        $notification->delete();
    }

    public function deleteAllForUser(int $userId, ?int $organizationId = null): int
    {
        $query = Notification::query()
            ->where('user_id', $userId);

        if ($organizationId !== null) {
            $query->where('organization_id', $organizationId);
        }

        return $query->delete();
    }

    public function countUnread(int $userId, ?int $organizationId = null): int
    {
        $query = Notification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at');

        if ($organizationId !== null) {
            $query->where('organization_id', $organizationId);
        }

        return $query->count();
    }
}
