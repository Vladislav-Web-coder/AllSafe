<?php

namespace App\Interfaces\Http\Controllers\Api\V1;

use App\Application\Organizations\Commands\AddOrganizationMemberCommand;
use App\Application\Organizations\Commands\RemoveOrganizationMemberCommand;
use App\Application\Organizations\Commands\UpdateOrganizationMemberRoleCommand;
use App\Application\Organizations\UseCases\AddOrganizationMemberUseCase;
use App\Application\Organizations\UseCases\RemoveOrganizationMemberUseCase;
use App\Application\Organizations\UseCases\UpdateOrganizationMemberRoleUseCase;
use App\Domain\Organizations\Enums\OrganizationRole;
use App\Domain\Organizations\Repositories\OrganizationMemberRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Interfaces\Http\Requests\Organizations\StoreOrganizationMemberRequest;
use App\Interfaces\Http\Requests\Organizations\UpdateOrganizationMemberRequest;
use App\Interfaces\Http\Resources\Organizations\OrganizationMemberResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationMemberController extends Controller
{
    public function __construct(
        private OrganizationMemberRepositoryInterface $members,
        private AddOrganizationMemberUseCase $addMember,
        private UpdateOrganizationMemberRoleUseCase $updateMemberRole,
        private RemoveOrganizationMemberUseCase $removeMember,
    ) {}

    public function index(Request $request)
    {
        $organization = $request->attributes->get('currentOrganization');

        $members = $this->members->listForOrganization($organization);

        return OrganizationMemberResource::collection($members);
    }

    public function store(StoreOrganizationMemberRequest $request): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        $command = new AddOrganizationMemberCommand(
            organization: $organization,
            email: $request->validated('email'),
            role: OrganizationRole::from($request->validated('role')),
            invitedByUserId: $request->user()->id,
        );

        $membership = $this->addMember->handle($command);

        return response()->json(new OrganizationMemberResource($membership), 201);
    }

    public function update(
        UpdateOrganizationMemberRequest $request,
        int $organizationId,
        int $userId,
    ): JsonResponse {
        $organization = $request->attributes->get('currentOrganization');

        $command = new UpdateOrganizationMemberRoleCommand(
            organization: $organization,
            userId: $userId,
            role: OrganizationRole::from($request->validated('role')),
        );

        $membership = $this->updateMemberRole->handle($command);

        return response()->json(new OrganizationMemberResource($membership));
    }

    public function destroy(Request $request, int $organizationId, int $userId): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        $command = new RemoveOrganizationMemberCommand(
            organization: $organization,
            userId: $userId,
        );

        $this->removeMember->handle($command);

        return response()->json(null, 204);
    }
}
