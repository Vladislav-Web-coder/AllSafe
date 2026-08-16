<?php

namespace App\Infrastructure\AI\Support;

use Illuminate\Support\Facades\Log;

class LlmJsonParser
{
    /**
     * Пытается извлечь JSON из ответа LLM.
     * Возвращает массив или null, если JSON не найден.
     */
    public function parse(string $raw): ?array
    {
        // Убираем markdown code fences
        $cleaned = $this->stripCodeFences($raw);

        // распарсить напрямую
        $decoded = json_decode($cleaned, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        // Если не получилось, пробуем найти JSON в строке
        $extracted = $this->extractJson($cleaned);

        if ($extracted !== null) {
            return $extracted;
        }

        // Пробуем починить обрезанный JSON
        $repaired = $this->repairTruncatedJson($cleaned);

        if ($repaired !== null) {
            return $repaired;
        }

        Log::warning('LlmJsonParser: failed to parse JSON', [
            'raw_preview' => mb_substr($raw, 0, 300),
            'json_error' => json_last_error_msg(),
        ]);

        return null;
    }

    /**
     * Убирает markdown code fences.
     */
    private function stripCodeFences(string $text): string
    {
        $text = trim($text);

        // Убираем ```json ... ``` или ``` ... ```
        $text = (string) preg_replace('/^```(?:json)?\s*\n?/i', '', $text);
        $text = (string) preg_replace('/\n?```\s*$/', '', $text);

        return trim($text);
    }

    /**
     * Пытается найти JSON-объект в строке.
     */
    private function extractJson(string $text): ?array
    {
        // Ищем первый { и последний }
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

    /**
     * Пытается починить обрезанный JSON.
     * Если JSON не завершён, пробуем закрыть открытые скобки.
     */
    private function repairTruncatedJson(string $text): ?array
    {
        // Находим начало JSON
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

        // Пробуем закрыть обрезанные строки и скобки
        $repaired = $this->closeOpenBrackets($jsonCandidate);

        $decoded = json_decode($repaired, true);

        if (is_array($decoded)) {
            Log::info('LlmJsonParser: repaired truncated JSON');
            return $decoded;
        }

        return null;
    }

    /**
     * Закрывает открытые скобки и кавычки в обрезанном JSON.
     */
    private function closeOpenBrackets(string $json): string
    {
        // Убираем незавершённую строку в конце
        // Ищем последнюю завершённую пару ключ-значение
        $json = preg_replace('/,\s*"[^"]*":\s*"[^"]*$/', '', $json);
        $json = preg_replace('/,\s*"[^"]*":\s*\[$/', '', $json);
        $json = preg_replace('/,\s*$/', '', $json);

        // Считаем открытые скобки
        $openBraces = substr_count($json, '{') - substr_count($json, '}');
        $openBrackets = substr_count($json, '[') - substr_count($json, ']');

        // Проверяем, есть ли незакрытая строка
        $quoteCount = substr_count($json, '"');
        if ($quoteCount % 2 !== 0) {
            $json .= '"';
        }

        // Закрываем открытые скобки
        for ($i = 0; $i < $openBrackets; $i++) {
            $json .= ']';
        }

        for ($i = 0; $i < $openBraces; $i++) {
            $json .= '}';
        }

        return $json;
    }
}
