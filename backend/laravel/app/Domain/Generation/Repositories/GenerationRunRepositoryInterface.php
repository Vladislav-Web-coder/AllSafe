<?php

namespace App\Domain\Generation\Repositories;

use App\Domain\Generation\Entities\GenerationRun;
use Illuminate\Database\Eloquent\Collection;

interface GenerationRunRepositoryInterface
{
    public function create(array $data): GenerationRun;

    public function findById(int $id): ?GenerationRun;

    public function update(GenerationRun $run, array $data): GenerationRun;

    public function listForOrganization(int $organizationId): Collection;
}
