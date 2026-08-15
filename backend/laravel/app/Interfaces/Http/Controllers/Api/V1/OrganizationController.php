<?php

namespace App\Interfaces\Http\Controllers\Api\V1;

use App\Application\Organizations\Commands\CreateOrganizationCommand;
use App\Application\Organizations\Commands\UpdateOrganizationCommand;
use App\Application\Organizations\UseCases\CreateOrganizationUseCase;
use App\Application\Organizations\UseCases\UpdateOrganizationUseCase;
use App\Domain\Organizations\Repositories\OrganizationRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Interfaces\Http\Requests\Organizations\StoreOrganizationRequest;
use App\Interfaces\Http\Requests\Organizations\UpdateOrganizationRequest;
use App\Interfaces\Http\Resources\Organizations\OrganizationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function __construct(
        private OrganizationRepositoryInterface $organizations,
        private CreateOrganizationUseCase $createOrganization,
        private UpdateOrganizationUseCase $updateOrganization,
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
}
