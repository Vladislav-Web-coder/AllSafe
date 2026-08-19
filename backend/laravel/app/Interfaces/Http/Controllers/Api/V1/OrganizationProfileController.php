<?php

namespace App\Interfaces\Http\Controllers\Api\V1;

use App\Application\Profiles\Commands\UpdateOrganizationProfileCommand;
use App\Application\Profiles\UseCases\UpdateOrganizationProfileUseCase;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Audit\Services\AuditService;
use App\Domain\Documents\Entities\Document;
use App\Domain\Profiles\Repositories\OrganizationProfileRepositoryInterface;
use App\Domain\Requirements\Services\RequiredDocumentsCalculator;
use App\Http\Controllers\Controller;
use App\Interfaces\Http\Requests\Profiles\UpdateOrganizationProfileRequest;
use App\Interfaces\Http\Resources\Profiles\OrganizationProfileResource;
use App\Interfaces\Http\Resources\Requirements\RequiredDocumentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationProfileController extends Controller
{
    public function __construct(
        private OrganizationProfileRepositoryInterface $profiles,
        private UpdateOrganizationProfileUseCase $updateProfile,
        private RequiredDocumentsCalculator $calculator,
        private AuditService $audit
    ) {}

    public function show(Request $request, int $organizationId)
    {
        $organization = $request->attributes->get('currentOrganization');

        $profile = $this->profiles->findByOrganizationId($organization->id);

        if (! $profile) {
            return response()->json([
                'message' => 'Профиль организации ещё не заполнен.',
                'data' => null,
            ], 404);
        }

        return new OrganizationProfileResource($profile);
    }

    public function update(UpdateOrganizationProfileRequest $request, int $organizationId): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        $command = new UpdateOrganizationProfileCommand(
            organizationId: $organization->id,
            processesPersonalData: (bool) $request->validated('processes_personal_data'),
            hasWebsite: (bool) $request->validated('has_website'),
            hasGis: (bool) $request->validated('has_gis'),
            hasKii: (bool) $request->validated('has_kii'),
            hasAsutp: (bool) $request->validated('has_asutp'),
            usesCloud: (bool) $request->validated('uses_cloud'),
            hasContractors: (bool) $request->validated('has_contractors'),
            hasCrossBorderTransfer: (bool) $request->validated('has_cross_border_transfer'),
            dataCategories: $request->validated('data_categories'),
            specialDataCategories: $request->validated('special_data_categories'),
            subjectsCount: $request->validated('subjects_count'),
            protectionLevel: $request->validated('protection_level'),
            extraAttributes: $request->validated('extra_attributes'),
        );

        $profile = $this->updateProfile->handle($command);

        $this->audit->logFromRequest(
            action: AuditAction::ProfileUpdated,
            request: $request,
            subjectType: 'organization_profile',
            subjectId: $profile->id,
            description: 'Обновлён профиль организации',
            newValues: $request->validated(),
        );

        return response()->json(new OrganizationProfileResource($profile));
    }

    public function requiredDocuments(Request $request, int $organizationId)
    {
        $organization = $request->attributes->get('currentOrganization');

        $profile = $this->profiles->findByOrganizationId($organization->id);

        if (! $profile) {
            return response()->json([
                'message' => 'Профиль организации ещё не заполнен. Заполните профиль для подбора документов.',
                'data' => [],
            ], 422);
        }

        $requiredRules = $this->calculator->calculate($profile);

        // Получаем типы документов, которые уже есть в организации
        $existingDocumentTypeIds = Document::query()
            ->where('organization_id', $organization->id)
            ->whereNotNull('document_type_id')
            ->pluck('document_type_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->toArray();

        // Добавляем is_present к каждому правилу
        $rulesWithPresence = $requiredRules->map(function ($rule) use ($existingDocumentTypeIds) {
            $rule->is_present = in_array((int) $rule->document_type_id, $existingDocumentTypeIds, true);
            return $rule;
        });

        return RequiredDocumentResource::collection($rulesWithPresence);
    }

    public function missingDocuments(Request $request, int $organizationId)
    {
        $organization = $request->attributes->get('currentOrganization');

        $profile = $this->profiles->findByOrganizationId($organization->id);

        if (! $profile) {
            return response()->json([
                'message' => 'Профиль организации ещё не заполнен. Заполните профиль для подбора документов.',
                'data' => [],
            ], 422);
        }

        $requiredRules = $this->calculator->calculate($profile);

        // Получаем типы документов, которые уже есть в организации
        $existingDocumentTypeIds = Document::query()
            ->where('organization_id', $organization->id)
            ->whereNotNull('document_type_id')
            ->pluck('document_type_id')
            ->unique()
            ->toArray();

        // Фильтруем правила: оставляем только те, для которых документа ещё нет
        $missingRules = $requiredRules->filter(function ($rule) use ($existingDocumentTypeIds) {
            return ! in_array($rule->document_type_id, $existingDocumentTypeIds);
        });

        return RequiredDocumentResource::collection($missingRules);
    }
}
