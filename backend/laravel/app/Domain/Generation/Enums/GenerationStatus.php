<?php

namespace App\Domain\Generation\Enums;

enum GenerationStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Ожидает обработки',
            self::Processing => 'Генерируется',
            self::Completed => 'Завершена',
            self::Failed => 'Ошибка',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Pending, self::Processing]);
    }
}
