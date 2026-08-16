<?php

namespace App\Infrastructure\AI\Support;

class AnalysisResultNormalizer
{
    public function normalize(array $raw, string $provider, string $model): array
    {
        $score = null;

        if (isset($raw['score'])) {
            $score = (int) $raw['score'];
            $score = max(0, min(100, $score));
        }

        $summary = null;

        if (isset($raw['summary']) && is_array($raw['summary'])) {
            $summary = $raw['summary'];
        }

        $missingSections = array_values(array_filter(
            $raw['missing_sections'] ?? [],
            fn ($item) => is_string($item) && trim($item) !== ''
        ));

        $legalReferences = array_values(array_filter(
            $raw['legal_references'] ?? [],
            fn ($item) => is_string($item) && trim($item) !== ''
        ));

        $issues = [];

        foreach ($raw['issues'] ?? [] as $issue) {
            if (! is_array($issue)) {
                continue;
            }

            $issues[] = [
                'requirement_code' => $issue['requirement_code'] ?? null,
                'severity' => $issue['severity'] ?? 'info',
                'title' => $issue['title'] ?? 'Замечание',
                'description' => $issue['description'] ?? null,
                'recommendation' => $issue['recommendation'] ?? null,
                'legal_basis' => is_array($issue['legal_basis'] ?? null)
                    ? $issue['legal_basis']
                    : [],
                'section_code' => $issue['section_code'] ?? null,
            ];
        }

        return [
            'score' => $score,
            'summary' => $summary,
            'missing_sections' => $missingSections,
            'legal_references' => $legalReferences,
            'issues' => $issues,
            'model_provider' => $provider,
            'model_name' => $model,
        ];
    }
}
