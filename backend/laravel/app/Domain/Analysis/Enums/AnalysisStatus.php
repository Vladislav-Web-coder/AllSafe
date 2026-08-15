<?php

namespace App\Domain\Analysis\Enums;

enum AnalysisStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Ожидает обработки',
            self::Processing => 'Обрабатывается',
            self::Completed => 'Завершён',
            self::Failed => 'Ошибка',
            self::Cancelled => 'Отменён',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [
            self::Pending,
            self::Processing,
        ]);
    }
}
