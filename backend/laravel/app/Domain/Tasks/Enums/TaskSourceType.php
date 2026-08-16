<?php

namespace App\Domain\Tasks\Enums;

enum TaskSourceType: string
{
    case Manual = 'manual';
    case Issue = 'issue';
    case Analysis = 'analysis';
    case Generation = 'generation';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Создана вручную',
            self::Issue => 'Из замечания',
            self::Analysis => 'Из анализа',
            self::Generation => 'Из генерации',
        };
    }
}
