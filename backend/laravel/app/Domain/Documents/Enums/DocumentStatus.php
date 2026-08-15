<?php

namespace App\Domain\Documents\Enums;

enum DocumentStatus: string
{
    case Draft = 'draft';
    case Uploaded = 'uploaded';
    case Queued = 'queued';
    case Processing = 'processing';
    case Analyzing = 'analyzing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Черновик',
            self::Uploaded => 'Загружен',
            self::Queued => 'В очереди',
            self::Processing => 'Обрабатывается',
            self::Analyzing => 'Анализируется',
            self::Completed => 'Проверен',
            self::Failed => 'Ошибка',
            self::Archived => 'Архив',
        };
    }
}
