<?php

namespace App\Domain\Tasks\Enums;

enum TaskStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Blocked = 'blocked';
    case Done = 'done';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Новая',
            self::InProgress => 'В работе',
            self::Blocked => 'Заблокирована',
            self::Done => 'Выполнена',
            self::Cancelled => 'Отменена',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::New, self::InProgress, self::Blocked]);
    }

    public function isCompleted(): bool
    {
        return $this === self::Done;
    }
}
