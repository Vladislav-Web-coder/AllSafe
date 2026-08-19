<?php

namespace App\Interfaces\Http\Controllers\Api\V1;

use App\Application\Organizations\Commands\CreateOrganizationCommand;
use App\Application\Organizations\Commands\UpdateOrganizationCommand;
use App\Application\Organizations\UseCases\CreateOrganizationUseCase;
use App\Application\Organizations\UseCases\UpdateOrganizationUseCase;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Audit\Services\AuditService;
use App\Domain\Organizations\Repositories\OrganizationRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Interfaces\Http\Requests\Organizations\StoreOrganizationRequest;
use App\Interfaces\Http\Requests\Organizations\UpdateOrganizationRequest;
use App\Interfaces\Http\Resources\Organizations\OrganizationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrganizationController extends Controller
{
    public function __construct(
        private OrganizationRepositoryInterface $organizations,
        private CreateOrganizationUseCase $createOrganization,
        private UpdateOrganizationUseCase $updateOrganization,
        private AuditService $audit
    ) {}

    public function index(Request $request)
    {
        $organizations = $this->organizations->getForUser($request->user());

        return OrganizationResource::collection($organizations);
    }

    public function store(StoreOrganizationRequest $request): JsonResponse
    {
        $command = new CreateOrganizationCommand(
            ownerId: $request->user()->id,
            name: $request->validated('name'),
            legalName: $request->validated('legal_name'),
            inn: $request->validated('inn'),
            organizationTypeId: (int) $request->validated('organization_type_id'),
            industryId: (int) $request->validated('industry_id'),
        );

        $organization = $this->createOrganization->handle($command);

        return response()->json(new OrganizationResource($organization), 201);
    }

    public function show(Request $request)
    {
        $organization = $request->attributes->get('currentOrganization');

        $organization->load(['type', 'industry']);

        return new OrganizationResource($organization);
    }

    public function update(UpdateOrganizationRequest $request): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        $command = new UpdateOrganizationCommand(
            organization: $organization,
            name: $request->validated('name'),
            legalName: $request->validated('legal_name'),
            inn: $request->validated('inn'),
            organizationTypeId: (int) $request->validated('organization_type_id'),
            industryId: (int) $request->validated('industry_id'),
        );

        $organization = $this->updateOrganization->handle($command);

        return response()->json(new OrganizationResource($organization));
    }

    public function leave(Request $request, int $organizationId): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');
        $user = $request->user();

        if ($organization->isOwner($user)) {
            throw ValidationException::withMessages([
                'organization' => ['Владелец не может выйти из организации. Передайте права владельца или удалите организацию.'],
            ]);
        }

        DB::connection('pgsql_identity')->transaction(function () use ($organization, $user) {
            $organization->members()->detach($user->id);
        });

        $this->audit->log(
            action: AuditAction::MemberRemoved,
            userId: $user->id,
            userEmail: $user->email,
            organizationId: $organization->id,
            description: "Пользователь вышел из организации: {$organization->name}",
        );

        return response()->json([
            'message' => 'Вы вышли из организации.',
        ]);
    }

    public function destroy(Request $request, int $organizationId): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');
        $user = $request->user();

        // Проверяем, что пользователь владелец через прямой запрос
        $membership = DB::connection('pgsql_identity')
            ->table('organization_user')
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $membership || $membership->role !== 'owner') {
            throw ValidationException::withMessages([
                'organization' => ['Только владелец может удалить организацию.'],
            ]);
        }

        DB::transaction(function () use ($organization) {
            DB::connection('pgsql_core')->table('tasks')
                ->where('organization_id', $organization->id)
                ->delete();

            DB::connection('pgsql_core')->table('issue_comments')
                ->whereIn('document_issue_id', function ($query) use ($organization) {
                    $query->select('id')
                        ->from('document_issues')
                        ->where('organization_id', $organization->id);
                })
                ->delete();

            DB::connection('pgsql_core')->table('issue_history')
                ->whereIn('document_issue_id', function ($query) use ($organization) {
                    $query->select('id')
                        ->from('document_issues')
                        ->where('organization_id', $organization->id);
                })
                ->delete();

            DB::connection('pgsql_core')->table('document_issues')
                ->where('organization_id', $organization->id)
                ->delete();

            DB::connection('pgsql_core')->table('document_versions')
                ->whereIn('document_id', function ($query) use ($organization) {
                    $query->select('id')
                        ->from('documents')
                        ->where('organization_id', $organization->id);
                })
                ->delete();

            DB::connection('pgsql_core')->table('documents')
                ->where('organization_id', $organization->id)
                ->delete();

            DB::connection('pgsql_core')->table('generated_documents')
                ->whereIn('generation_run_id', function ($query) use ($organization) {
                    $query->select('id')
                        ->from('generation_runs')
                        ->where('organization_id', $organization->id);
                })
                ->delete();

            DB::connection('pgsql_core')->table('generation_runs')
                ->where('organization_id', $organization->id)
                ->delete();

            DB::connection('pgsql_identity')->table('organization_profiles')
                ->where('organization_id', $organization->id)
                ->delete();

            // Участники
            $organization->members()->detach();

            // Soft delete организации
            $organization->delete();
        });

        $this->audit->log(
            action: AuditAction::OrganizationUpdated,
            userId: $user->id,
            userEmail: $user->email,
            organizationId: $organization->id,
            description: "Организация удалена: {$organization->name}",
        );

        return response()->json([
            'message' => 'Организация удалена.',
        ]);
    }
}
