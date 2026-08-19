<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Organizations\Entities\OrganizationInvitation;
use App\Domain\Organizations\Repositories\OrganizationInvitationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentOrganizationInvitationRepository implements OrganizationInvitationRepositoryInterface
{
    public function create(array $data): OrganizationInvitation
    {
        return OrganizationInvitation::query()->create($data);
    }

    public function findByToken(string $token): ?OrganizationInvitation
    {
        return OrganizationInvitation::query()
            ->where('token', $token)
            ->with('organization')
            ->first();
    }

    public function findByEmailAndOrganization(string $email, int $organizationId): ?OrganizationInvitation
    {
        return OrganizationInvitation::query()
            ->where('email', $email)
            ->where('organization_id', $organizationId)
            ->where('status', 'pending')
            ->first();
    }

    public function listForOrganization(int $organizationId): Collection
    {
        return OrganizationInvitation::query()
            ->where('organization_id', $organizationId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function update(OrganizationInvitation $invitation, array $data): OrganizationInvitation
    {
        $invitation->forceFill($data)->save();

        return $invitation->refresh();
    }

    public function delete(OrganizationInvitation $invitation): void
    {
        $invitation->delete();
    }
}
