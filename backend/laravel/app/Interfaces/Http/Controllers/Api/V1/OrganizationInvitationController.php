<?php

namespace App\Interfaces\Http\Controllers\Api\V1;

use App\Application\Organizations\Commands\InviteMemberCommand;
use App\Application\Organizations\UseCases\AcceptInvitationUseCase;
use App\Application\Organizations\UseCases\InviteMemberUseCase;
use App\Domain\Organizations\Repositories\OrganizationInvitationRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrganizationInvitationController extends Controller
{
    public function __construct(
        private InviteMemberUseCase $inviteMember,
        private AcceptInvitationUseCase $acceptInvitation,
        private OrganizationInvitationRepositoryInterface $invitations,
    ) {}

    /**
     * Список приглашений организации.
     */
    public function index(Request $request, int $organizationId)
    {
        $organization = $request->attributes->get('currentOrganization');

        $invitations = $this->invitations->listForOrganization($organization->id);

        return response()->json([
            'data' => $invitations,
        ]);
    }

    /**
     * Отправить приглашение.
     */
    public function store(Request $request, int $organizationId): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => [
                'required',
                'string',
                Rule::in(['admin', 'security_officer', 'legal_officer', 'auditor', 'employee', 'viewer']),
            ],
        ]);

        $command = new InviteMemberCommand(
            organizationId: $organization->id,
            email: $validated['email'],
            role: $validated['role'],
            invitedBy: $request->user()->id,
        );

        $invitation = $this->inviteMember->handle($command);

        return response()->json([
            'message' => 'Приглашение отправлено.',
            'data' => $invitation,
        ], 201);
    }

    /**
     * Принять приглашение.
     */
    public function accept(Request $request, string $token): JsonResponse
    {
        $this->acceptInvitation->handle($token, $request->user());

        return response()->json([
            'message' => 'Приглашение принято. Вы добавлены в организацию.',
        ]);
    }

    /**
     * Отменить приглашение.
     */
    public function destroy(Request $request, int $organizationId, int $invitationId): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        $invitation = $this->invitations->findByToken('');

        // Ищем по ID
        $invitation = \App\Domain\Organizations\Entities\OrganizationInvitation::query()
            ->where('id', $invitationId)
            ->where('organization_id', $organization->id)
            ->first();

        if (! $invitation) {
            abort(404, 'Приглашение не найдено.');
        }

        if ($invitation->status !== 'pending') {
            return response()->json([
                'message' => 'Приглашение уже было обработано.',
            ], 422);
        }

        $this->invitations->update($invitation, [
            'status' => 'cancelled',
        ]);

        return response()->json([
            'message' => 'Приглашение отменено.',
        ]);
    }
}
