<?php

namespace App\Domain\Notifications\Repositories;

use App\Domain\Notifications\Entities\Notification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface NotificationRepositoryInterface
{
    public function create(array $data): Notification;

    public function findById(int $id): ?Notification;

    public function listForUser(int $userId, ?int $organizationId = null, bool $unreadOnly = false, int $perPage = 20): LengthAwarePaginator;

    public function markAsRead(Notification $notification): void;

    public function markAllAsRead(int $userId, ?int $organizationId = null): int;

    public function delete(Notification $notification): void;

    public function deleteAllForUser(int $userId, ?int $organizationId = null): int;

    public function countUnread(int $userId, ?int $organizationId = null): int;
}
