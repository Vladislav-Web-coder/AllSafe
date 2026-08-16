<?php

namespace App\Domain\Audit\Services;

use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Audit\Repositories\AuditLogRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuditService
{
    public function __construct(
        private AuditLogRepositoryInterface $repository,
    ) {}

    /**
     * Записывает событие аудита.
     */
    public function log(
        AuditAction $action,
        ?int $userId = null,
        ?string $userEmail = null,
        ?int $organizationId = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        string $result = 'success',
        ?Request $request = null,
    ): void {
        try {
            $data = [
                'user_id' => $userId,
                'user_email' => $userEmail,
                'organization_id' => $organizationId,
                'action' => $action->value,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'description' => $description,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'result' => $result,
                'created_at' => now(),
            ];

            if ($request) {
                $data['ip_address'] = $request->ip();
                $data['user_agent'] = mb_substr($request->userAgent() ?? '', 0, 500);
                $data['request_id'] = (string) Str::uuid();
            }

            $this->repository->create($data);
        } catch (\Throwable $e) {
            // Аудит не должен ломать основной поток
            Log::error('AuditService: failed to write audit log', [
                'action' => $action->value,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Записывает событие из текущего HTTP-запроса.
     */
    public function logFromRequest(
        AuditAction $action,
        Request $request,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        string $result = 'success',
    ): void {
        $user = $request->user();

        $organization = $request->attributes->get('currentOrganization');

        $this->log(
            action: $action,
            userId: $user?->id,
            userEmail: $user?->email,
            organizationId: $organization?->id,
            subjectType: $subjectType,
            subjectId: $subjectId,
            description: $description,
            oldValues: $oldValues,
            newValues: $newValues,
            result: $result,
            request: $request,
        );
    }
}
