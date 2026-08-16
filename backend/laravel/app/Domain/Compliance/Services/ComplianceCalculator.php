<?php

namespace App\Domain\Compliance\Services;

use App\Domain\Analysis\Enums\IssueSeverity;
use App\Domain\Analysis\Enums\IssueStatus;
use App\Domain\Analysis\Repositories\DocumentIssueRepositoryInterface;
use App\Domain\Documents\Entities\Document;
use App\Domain\Documents\Enums\DocumentStatus;
use App\Domain\Documents\Repositories\DocumentRepositoryInterface;
use App\Domain\Profiles\Entities\OrganizationProfile;
use App\Domain\Requirements\Entities\DocumentRequirementRule;
use App\Domain\Requirements\Repositories\DocumentRequirementRuleRepositoryInterface;
use App\Domain\Requirements\Services\RequiredDocumentsCalculator;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Domain\Tasks\Repositories\TaskRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ComplianceCalculator
{
    public function __construct(
        private RequiredDocumentsCalculator $requiredCalculator,
        private DocumentRequirementRuleRepositoryInterface $rules,
        private DocumentRepositoryInterface $documents,
        private DocumentIssueRepositoryInterface $issues,
        private TaskRepositoryInterface $tasks,
    ) {}

    /**
     * Рассчитывает полную комплаенс-картину для организации.
     */
    public function calculate(int $organizationId, ?OrganizationProfile $profile): array
    {
        $requiredDocuments = collect();

        if ($profile) {
            $requiredDocuments = $this->requiredCalculator->calculate($profile);
        }

        $existingDocuments = $this->documents->listForOrganization($organizationId);

        $documentCompliance = $this->calculateDocumentCompliance(
            $requiredDocuments,
            $existingDocuments
        );

        $issueStats = $this->calculateIssueStats($organizationId);
        $taskStats = $this->tasks->countByStatus($organizationId);

        $overallScore = $this->calculateOverallScore(
            $documentCompliance,
            $issueStats
        );

        $recommendations = $this->generateRecommendations(
            $documentCompliance,
            $issueStats,
            $taskStats
        );

        return [
            'organization_id' => $organizationId,
            'profile_filled' => $profile !== null,
            'overall_score' => $overallScore,
            'documents' => $documentCompliance,
            'issues' => $issueStats,
            'tasks' => $taskStats,
            'recommendations' => $recommendations,
            'calculated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Рассчитывает соответствие по документам.
     */
    private function calculateDocumentCompliance(
        Collection $requiredDocuments,
        Collection $existingDocuments,
    ): array {
        $existingTypeIds = $existingDocuments
            ->pluck('document_type_id')
            ->unique()
            ->toArray();

        $required = [];
        $missing = [];
        $present = [];

        foreach ($requiredDocuments as $rule) {
            $documentTypeId = $rule->document_type_id;

            $item = [
                'rule_code' => $rule->code,
                'document_type_id' => $documentTypeId,
                'document_type_code' => $rule->documentType?->code,
                'document_type_name' => $rule->documentType?->name,
                'obligation_level' => $rule->obligation_level,
                'priority' => $rule->priority,
                'legal_basis' => $rule->legal_basis_json,
                'is_present' => in_array($documentTypeId, $existingTypeIds),
            ];

            $required[] = $item;

            if ($item['is_present']) {
                $present[] = $item;
            } else {
                $missing[] = $item;
            }
        }

        $totalRequired = count($required);
        $totalPresent = count($present);
        $totalMissing = count($missing);

        $compliancePercent = $totalRequired > 0
            ? round(($totalPresent / $totalRequired) * 100)
            : 0;

        return [
            'total_required' => $totalRequired,
            'total_present' => $totalPresent,
            'total_missing' => $totalMissing,
            'compliance_percent' => $compliancePercent,
            'required' => $required,
            'missing' => $missing,
            'existing_documents_count' => $existingDocuments->count(),
            'documents_by_status' => $this->groupDocumentsByStatus($existingDocuments),
        ];
    }

    /**
     * Группирует документы по статусам.
     */
    private function groupDocumentsByStatus(Collection $documents): array
    {
        $grouped = [];

        foreach (DocumentStatus::cases() as $status) {
            $grouped[$status->value] = $documents
                ->where('status', $status)
                ->count();
        }

        return $grouped;
    }

    /**
     * Рассчитывает статистику по замечаниям.
     */
    private function calculateIssueStats(int $organizationId): array
    {
        $issues = $this->issues->listForOrganization($organizationId);

        $bySeverity = [];
        foreach (IssueSeverity::cases() as $severity) {
            $bySeverity[$severity->value] = $issues
                ->where('severity', $severity)
                ->count();
        }

        $byStatus = [];
        foreach (IssueStatus::cases() as $status) {
            $byStatus[$status->value] = $issues
                ->where('status', $status)
                ->count();
        }

        $openIssues = $issues->whereIn('status', [IssueStatus::Open, IssueStatus::Accepted]);

        $criticalOpen = $openIssues
            ->whereIn('severity', [IssueSeverity::Critical, IssueSeverity::High])
            ->count();

        return [
            'total' => $issues->count(),
            'open' => $openIssues->count(),
            'resolved' => $issues->whereIn('status', [IssueStatus::Fixed, IssueStatus::Rejected])->count(),
            'critical_open' => $criticalOpen,
            'by_severity' => $bySeverity,
            'by_status' => $byStatus,
        ];
    }

    /**
     * Рассчитывает общий уровень соответствия.
     */
    private function calculateOverallScore(array $documentCompliance, array $issueStats): int
    {
        // Вес документов: 60%
        $documentScore = $documentCompliance['compliance_percent'] ?? 0;

        // Вес замечаний: 40%
        // Если нет замечаний — 100, если есть открытые критические — снижаем
        $issueScore = 100;

        if ($issueStats['total'] > 0) {
            $resolvedPercent = $issueStats['total'] > 0
                ? ($issueStats['resolved'] / $issueStats['total']) * 100
                : 100;

            $issueScore = (int) $resolvedPercent;

            // Штраф за открытые критические замечания
            $criticalPenalty = min($issueStats['critical_open'] * 5, 30);
            $issueScore = max(0, $issueScore - $criticalPenalty);
        }

        $overall = (int) round(($documentScore * 0.6) + ($issueScore * 0.4));

        return max(0, min(100, $overall));
    }

    /**
     * Генерирует рекомендации.
     */
    private function generateRecommendations(
        array $documentCompliance,
        array $issueStats,
        array $taskStats,
    ): array {
        $recommendations = [];

        // Отсутствующие обязательные документы
        $missingRequired = array_filter(
            $documentCompliance['missing'] ?? [],
            fn ($item) => $item['obligation_level'] === 'required'
        );

        if (! empty($missingRequired)) {
            $names = array_map(fn ($item) => $item['document_type_name'], $missingRequired);

            $recommendations[] = [
                'type' => 'missing_documents',
                'priority' => 'high',
                'title' => 'Отсутствуют обязательные документы',
                'description' => 'Необходимо создать: ' . implode(', ', $names),
                'count' => count($missingRequired),
            ];
        }

        // Открытые критические замечания
        if ($issueStats['critical_open'] > 0) {
            $recommendations[] = [
                'type' => 'critical_issues',
                'priority' => 'critical',
                'title' => 'Открытые критические замечания',
                'description' => "Необходимо устранить {$issueStats['critical_open']} критических замечаний",
                'count' => $issueStats['critical_open'],
            ];
        }

        // Открытые замечания
        if ($issueStats['open'] > 0) {
            $recommendations[] = [
                'type' => 'open_issues',
                'priority' => 'medium',
                'title' => 'Открытые замечания',
                'description' => "В работе {$issueStats['open']} замечаний",
                'count' => $issueStats['open'],
            ];
        }

        // Просроченные задачи
        $overdueTasks = $taskStats['overdue'] ?? 0;
        if ($overdueTasks > 0) {
            $recommendations[] = [
                'type' => 'overdue_tasks',
                'priority' => 'high',
                'title' => 'Просроченные задачи',
                'description' => "Необходимо завершить {$overdueTasks} просроченных задач",
                'count' => $overdueTasks,
            ];
        }

        // Профиль не заполнен
        if (empty($documentCompliance['required'])) {
            $recommendations[] = [
                'type' => 'profile_not_filled',
                'priority' => 'high',
                'title' => 'Профиль организации не заполнен',
                'description' => 'Заполните профиль для определения обязательных документов',
                'count' => 0,
            ];
        }

        // Сортируем по приоритету
        $priorityOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];

        usort($recommendations, function ($a, $b) use ($priorityOrder) {
            return ($priorityOrder[$a['priority']] ?? 99) <=> ($priorityOrder[$b['priority']] ?? 99);
        });

        return $recommendations;
    }
}
