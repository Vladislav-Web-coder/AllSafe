<?php

namespace App\Domain\Tasks\Services;

use App\Domain\Tasks\Enums\TaskStatus;

class TaskStatusTransition
{
    private static array $transitions = [
        'new' => ['in_progress', 'cancelled'],
        'in_progress' => ['blocked', 'done', 'cancelled'],
        'blocked' => ['in_progress', 'cancelled'],
        'done' => ['in_progress'],
        'cancelled' => ['new'],
    ];

    public static function canTransition(TaskStatus $from, TaskStatus $to): bool
    {
        if ($from === $to) {
            return false;
        }

        $allowed = self::$transitions[$from->value] ?? [];

        return in_array($to->value, $allowed);
    }

    public static function getAllowedTransitions(TaskStatus $from): array
    {
        $allowedValues = self::$transitions[$from->value] ?? [];

        return array_map(
            fn ($value) => TaskStatus::from($value),
            $allowedValues
        );
    }
}
