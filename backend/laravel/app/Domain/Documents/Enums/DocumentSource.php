<?php

namespace App\Domain\Documents\Enums;

enum DocumentSource: string
{
    case Upload = 'upload';
    case Generated = 'generated';
    case Imported = 'imported';
    case Fixed = 'fixed';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Upload => 'Загрузка файла',
            self::Generated => 'Сгенерирован',
            self::Imported => 'Импортирован',
            self::Fixed => 'Исправленная версия',
            self::Manual => 'Создан вручную',
        };
    }
}
