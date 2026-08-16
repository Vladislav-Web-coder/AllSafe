<?php

namespace App\Infrastructure\AI\Support;

use Illuminate\Support\Facades\Log;

class LlmJsonParser
{
    public function parse(string $raw): ?array
    {
        if (empty(trim($raw))) {
            return null;
        }

        // Шаг 1: убираем markdown code fences
        $cleaned = $this->stripCodeFences($raw);

        // Шаг 2: пробуем распарсить напрямую
        $decoded = json_decode($cleaned, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        // Шаг 3: пробуем найти JSON-объект в строке
        $extracted = $this->extractJson($cleaned);

        if ($extracted !== null) {
            return $extracted;
        }

        // Шаг 4: пробуем починить обрезанный JSON
        $repaired = $this->repairTruncatedJson($cleaned);

        if ($repaired !== null) {
            return $repaired;
        }

        // Шаг 5: пробуем найти JSON с вложенными массивами
        $nested = $this->extractNestedJson($cleaned);

        if ($nested !== null) {
            return $nested;
        }

        Log::warning('LlmJsonParser: all parsing attempts failed', [
            'raw_length' => mb_strlen($raw),
            'raw_preview' => mb_substr($raw, 0, 300),
            'json_error' => json_last_error_msg(),
        ]);

        return null;
    }

    private function stripCodeFences(string $text): string
    {
        $text = trim($text);

        // Убираем ```json ... ``` или ``` ... ```
        $text = (string) preg_replace('/^```(?:json)?\s*\n?/i', '', $text);
        $text = (string) preg_replace('/\n?\s*```\s*$/', '', $text);

        return trim($text);
    }

    private function extractJson(string $text): ?array
    {
        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start === false || $end === false || $start >= $end) {
            return null;
        }

        $jsonCandidate = substr($text, $start, $end - $start + 1);

        $decoded = json_decode($jsonCandidate, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        return null;
    }

    private function extractNestedJson(string $text): ?array
    {
        // Ищем первую { и подбираем закрывающую скобку
        $start = strpos($text, '{');

        if ($start === false) {
            return null;
        }

        $depth = 0;
        $inString = false;
        $escaped = false;
        $length = strlen($text);

        for ($i = $start; $i < $length; $i++) {
            $char = $text[$i];

            if ($escaped) {
                $escaped = false;
                continue;
            }

            if ($char === '\\') {
                $escaped = true;
                continue;
            }

            if ($char === '"') {
                $inString = ! $inString;
                continue;
            }

            if ($inString) {
                continue;
            }

            if ($char === '{' || $char === '[') {
                $depth++;
            } elseif ($char === '}' || $char === ']') {
                $depth--;

                if ($depth === 0) {
                    $jsonCandidate = substr($text, $start, $i - $start + 1);

                    $decoded = json_decode($jsonCandidate, true);

                    if (is_array($decoded)) {
                        return $decoded;
                    }

                    break;
                }
            }
        }

        return null;
    }

    private function repairTruncatedJson(string $text): ?array
    {
        $start = strpos($text, '{');

        if ($start === false) {
            return null;
        }

        $jsonCandidate = substr($text, $start);

        // Пробуем распарсить как есть
        $decoded = json_decode($jsonCandidate, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        // Убираем незавершённые конструкции
        $repaired = preg_replace('/,\s*"[^"]*":\s*"[^"]*$/', '', $jsonCandidate);
        $repaired = preg_replace('/,\s*"[^"]*":\s*\{[^}]*$/', '', $repaired);
        $repaired = preg_replace('/,\s*"[^"]*":\s*\[[^\]]*$/', '', $repaired);
        $repaired = preg_replace('/,\s*$/', '', $repaired);

        // Закрываем незакрытую строку
        $quoteCount = substr_count($repaired, '"');
        if ($quoteCount % 2 !== 0) {
            $repaired .= '"';
        }

        // Закрываем скобки
        $openBraces = substr_count($repaired, '{') - substr_count($repaired, '}');
        $openBrackets = substr_count($repaired, '[') - substr_count($repaired, ']');

        for ($i = 0; $i < $openBrackets; $i++) {
            $repaired .= ']';
        }

        for ($i = 0; $i < $openBraces; $i++) {
            $repaired .= '}';
        }

        $decoded = json_decode($repaired, true);

        if (is_array($decoded)) {
            Log::info('LlmJsonParser: repaired truncated JSON successfully');
            return $decoded;
        }

        return null;
    }
}
