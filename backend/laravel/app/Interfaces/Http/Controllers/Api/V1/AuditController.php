<?php

namespace App\Interfaces\Http\Controllers\Api\V1;

use App\Domain\Audit\Repositories\AuditLogRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuditController extends Controller
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogs,
    ) {}

    /**
     * Список записей аудита с фильтрацией и пагинацией.
     */
    public function index(Request $request, int $organizationId): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        $perPage = min((int) $request->query('per_page', 50), 100);

        $logs = $this->auditLogs->paginate(
            perPage: $perPage,
            organizationId: $organization->id,
            action: $request->query('action'),
            userId: $request->query('user_id') ? (int) $request->query('user_id') : null,
            subjectType: $request->query('subject_type'),
            subjectId: $request->query('subject_id') ? (int) $request->query('subject_id') : null,
            dateFrom: $request->query('date_from'),
            dateTo: $request->query('date_to'),
        );

        return response()->json($logs);
    }

    /**
     * Конкретная запись аудита.
     */
    public function show(Request $request, int $organizationId, int $auditLogId): JsonResponse
    {
        $log = \App\Domain\Audit\Entities\AuditLog::query()
            ->where('id', $auditLogId)
            ->first();

        if (! $log) {
            abort(404, 'Запись аудита не найдена.');
        }

        return response()->json([
            'data' => $log,
        ]);
    }

    /**
     * Действия конкретного пользователя.
     */
    public function userActions(Request $request, int $organizationId, int $userId): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        $perPage = min((int) $request->query('per_page', 50), 100);

        $logs = $this->auditLogs->paginate(
            perPage: $perPage,
            organizationId: $organization->id,
            userId: $userId,
        );

        return response()->json($logs);
    }

    /**
     * Очистка аудита.
     * Доступна только владельцу организации.
     */
    public function clear(Request $request, int $organizationId): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        $deletedCount = \App\Domain\Audit\Entities\AuditLog::query()
            ->where('organization_id', $organization->id)
            ->delete();

        return response()->json([
            'message' => "Аудит очищен. Удалено записей: {$deletedCount}.",
            'deleted_count' => $deletedCount,
        ]);
    }
}
