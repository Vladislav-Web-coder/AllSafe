<?php

namespace App\Domain\Organizations\Repositories;

use App\Domain\Identity\Entities\User;
use App\Domain\Organizations\Entities\Organization;
use Illuminate\Database\Eloquent\Collection;

interface OrganizationRepositoryInterface
{
    public function findById(int $id): ?Organization;

    public function create(array $data): Organization;

    public function update(Organization $organization, array $data): Organization;

    public function getForUser(User $user): Collection;
    public function delete(Organization $organization): void;
}
