<?php

namespace App\Domain\Organizations\Repositories;

use App\Domain\Organizations\Entities\OrganizationInvitation;
use Illuminate\Database\Eloquent\Collection;

interface OrganizationInvitationRepositoryInterface
{
    public function create(array $data): OrganizationInvitation;

    public function findByToken(string $token): ?OrganizationInvitation;

    public function findByEmailAndOrganization(string $email, int $organizationId): ?OrganizationInvitation;

    public function listForOrganization(int $organizationId): Collection;

    public function update(OrganizationInvitation $invitation, array $data): OrganizationInvitation;

    public function delete(OrganizationInvitation $invitation): void;
}
