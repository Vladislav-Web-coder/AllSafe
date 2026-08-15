<?php

namespace App\Domain\Analysis\Enums;

enum IssueSeverity: string
{
    case Critical = 'critical';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';
    case Info = 'info';

    public function label(): string
    {
        return match ($this) {
            self::Critical => 'Критичная',
            self::High => 'Высокая',
            self::Medium => 'Средняя',
            self::Low => 'Низкая',
            self::Info => 'Информационная',
        };
    }
}
