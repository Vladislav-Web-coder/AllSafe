<?php

namespace App\Domain\Analysis\Services;

use Illuminate\Support\Facades\DB;

class AnalysisVersionService
{
    /**
     * Возвращает версию промпта.
     * Обновляй при изменении DocumentAnalysisPrompt.
     */
    public function getPromptVersion(): string
    {
        return 'analysis_prompt_v1.0';
    }

    /**
     * Возвращает версию базы знаний.
     * Основана на количестве источников и дате последнего обновления.
     */
    public function getKnowledgeBaseVersion(): string
    {
        $stats = DB::connection('pgsql_knowledge')
            ->table('legal_sources')
            ->selectRaw('count(*) as count, max(updated_at) as last_updated')
            ->first();

        $count = $stats->count ?? 0;
        $lastUpdated = $stats->last_updated
            ? date('Ymd', strtotime($stats->last_updated))
            : 'none';

        return "kb_{$count}_{$lastUpdated}";
    }

    /**
     * Возвращает версию требований.
     * Основана на количестве правил и дате последнего обновления.
     */
    public function getRequirementsVersion(): string
    {
        $stats = DB::connection('pgsql_core')
            ->table('document_requirement_rules')
            ->selectRaw('count(*) as count, max(updated_at) as last_updated')
            ->first();

        $count = $stats->count ?? 0;
        $lastUpdated = $stats->last_updated
            ? date('Ymd', strtotime($stats->last_updated))
            : 'none';

        return "req_{$count}_{$lastUpdated}";
    }

    /**
     * Возвращает все версии одним массивом.
     */
    public function getAllVersions(): array
    {
        return [
            'prompt_version' => $this->getPromptVersion(),
            'knowledge_base_version' => $this->getKnowledgeBaseVersion(),
            'requirements_version' => $this->getRequirementsVersion(),
        ];
    }
}
