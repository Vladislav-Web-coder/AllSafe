<?php

namespace App\Domain\Tasks\Enums;

enum TaskPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Низкий',
            self::Medium => 'Средний',
            self::High => 'Высокий',
            self::Critical => 'Критический',
        };
    }

    public function weight(): int
    {
        return match ($this) {
            self::Critical => 1,
            self::High => 2,
            self::Medium => 3,
            self::Low => 4,
        };
    }
}
