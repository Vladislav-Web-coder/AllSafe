<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Generation\Entities\GenerationRun;
use App\Domain\Generation\Repositories\GenerationRunRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentGenerationRunRepository implements GenerationRunRepositoryInterface
{
    public function create(array $data): GenerationRun
    {
        return GenerationRun::query()->create($data);
    }

    public function findById(int $id): ?GenerationRun
    {
        return GenerationRun::query()
            ->with(['template.documentType', 'generatedDocument'])
            ->find($id);
    }

    public function update(GenerationRun $run, array $data): GenerationRun
    {
        $run->update($data);

        return $run->refresh();
    }

    public function listForOrganization(int $organizationId): Collection
    {
        return GenerationRun::query()
            ->where('organization_id', $organizationId)
            ->with('template.documentType')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
