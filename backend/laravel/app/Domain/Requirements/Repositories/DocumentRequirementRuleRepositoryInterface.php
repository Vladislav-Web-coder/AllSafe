<?php

namespace App\Domain\Requirements\Repositories;

use App\Domain\Requirements\Entities\DocumentRequirementRule;
use Illuminate\Database\Eloquent\Collection;

interface DocumentRequirementRuleRepositoryInterface
{
    public function getActive(): Collection;

    public function findByCode(string $code): ?DocumentRequirementRule;
}
