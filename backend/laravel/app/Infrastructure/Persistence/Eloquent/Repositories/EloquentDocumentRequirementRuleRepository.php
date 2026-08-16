<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Requirements\Entities\DocumentRequirementRule;
use App\Domain\Requirements\Repositories\DocumentRequirementRuleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentDocumentRequirementRuleRepository implements DocumentRequirementRuleRepositoryInterface
{
    public function getActive(): Collection
    {
        return DocumentRequirementRule::query()
            ->where('is_active', true)
            ->with('documentType')
            ->orderBy('priority')
            ->get();
    }

    public function findByCode(string $code): ?DocumentRequirementRule
    {
        return DocumentRequirementRule::query()
            ->where('code', $code)
            ->first();
    }
}
