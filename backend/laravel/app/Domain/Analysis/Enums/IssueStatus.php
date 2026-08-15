<?php

namespace App\Domain\Analysis\Enums;

enum IssueStatus: string
{
    case Open = 'open';
    case Accepted = 'accepted';
    case Fixed = 'fixed';
    case Rejected = 'rejected';
    case Deferred = 'deferred';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Открыто',
            self::Accepted => 'Принято',
            self::Fixed => 'Исправлено',
            self::Rejected => 'Отклонено',
            self::Deferred => 'Отложено',
        };
    }
}
