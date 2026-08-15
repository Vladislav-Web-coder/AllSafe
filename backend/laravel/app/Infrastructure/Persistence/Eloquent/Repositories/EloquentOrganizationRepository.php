<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Identity\Entities\User;
use App\Domain\Organizations\Entities\Organization;
use App\Domain\Organizations\Repositories\OrganizationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentOrganizationRepository implements OrganizationRepositoryInterface
{
    public function findById(int $id): ?Organization
    {
        return Organization::query()
            ->with(['type', 'industry'])
            ->find($id);
    }

    public function create(array $data): Organization
    {
        return Organization::query()->create($data);
    }

    public function update(Organization $organization, array $data): Organization
    {
        $organization->update($data);

        return $organization->refresh();
    }

    public function getForUser(User $user): Collection
    {
        return Organization::query()
            ->whereHas('memberships', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with([
                'type',
                'industry',
                'memberships' => function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                },
            ])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
