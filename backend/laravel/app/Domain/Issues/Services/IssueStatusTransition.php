<?php

namespace App\Domain\Issues\Services;

use App\Domain\Analysis\Enums\IssueStatus;

class IssueStatusTransition
{
    /**
     * Допустимые переходы статусов.
     */
    private static array $transitions = [
        'open' => ['accepted', 'rejected', 'deferred'],
        'accepted' => ['fixed', 'deferred', 'open'],
        'fixed' => ['open'],
        'rejected' => ['open'],
        'deferred' => ['open', 'rejected', 'accepted'],
    ];

    public static function canTransition(IssueStatus $from, IssueStatus $to): bool
    {
        if ($from === $to) {
            return false;
        }

        $allowedTransitions = self::$transitions[$from->value] ?? [];

        return in_array($to->value, $allowedTransitions);
    }

    public static function getAllowedTransitions(IssueStatus $from): array
    {
        $allowedValues = self::$transitions[$from->value] ?? [];

        return array_map(
            fn ($value) => IssueStatus::from($value),
            $allowedValues
        );
    }
}
