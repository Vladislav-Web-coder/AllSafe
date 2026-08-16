<?php

namespace App\Interfaces\Http\Controllers\Api\V1;

use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Compliance\Services\ComplianceCalculator;
use App\Domain\Profiles\Repositories\OrganizationProfileRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComplianceController extends Controller
{
    public function __construct(
        private ComplianceCalculator $calculator,
        private OrganizationProfileRepositoryInterface $profiles,
    ) {}

    /**
     * Полный комплаенс-дашборд организации.
     */
    public function dashboard(Request $request, int $organizationId): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        $profile = $this->profiles->findByOrganizationId($organization->id);

        $result = $this->calculator->calculate($organization->id, $profile);

        $this->audit->logFromRequest(
            action: AuditAction::ComplianceDashboardViewed,
            request: $request,
            description: 'Просмотрен комплаенс-дашборд',
        );

        return response()->json([
            'data' => $result,
        ]);
    }

    /**
     * Краткая сводка для виджетов.
     */
    public function summary(Request $request, int $organizationId): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        $profile = $this->profiles->findByOrganizationId($organization->id);

        $result = $this->calculator->calculate($organization->id, $profile);

        return response()->json([
            'data' => [
                'overall_score' => $result['overall_score'],
                'profile_filled' => $result['profile_filled'],
                'documents' => [
                    'total_required' => $result['documents']['total_required'],
                    'total_present' => $result['documents']['total_present'],
                    'total_missing' => $result['documents']['total_missing'],
                    'compliance_percent' => $result['documents']['compliance_percent'],
                ],
                'issues' => [
                    'total' => $result['issues']['total'],
                    'open' => $result['issues']['open'],
                    'critical_open' => $result['issues']['critical_open'],
                ],
                'tasks' => [
                    'total' => $result['tasks']['total'],
                    'in_progress' => $result['tasks']['in_progress'],
                    'overdue' => $result['tasks']['overdue'],
                ],
                'recommendations_count' => count($result['recommendations']),
            ],
        ]);
    }
}
