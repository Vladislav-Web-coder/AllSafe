<?php

namespace App\Interfaces\Http\Controllers\Api\V1;

use App\Domain\Documents\Entities\DocumentType;
use App\Domain\Organizations\Entities\Industry;
use App\Domain\Organizations\Entities\OrganizationType;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class DictionaryController extends Controller
{
    public function organizationTypes(): JsonResponse
    {
        $types = OrganizationType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([
                'id',
                'code',
                'name',
                'description',
            ]);

        return response()->json([
            'data' => $types,
        ]);
    }

    public function industries(): JsonResponse
    {
        $industries = Industry::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([
                'id',
                'code',
                'name',
                'description',
                'kii_relevant',
            ]);

        return response()->json([
            'data' => $industries,
        ]);
    }
    public function documentTypes(): JsonResponse
    {
        $documentTypes = DocumentType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([
                'id',
                'code',
                'name',
                'category',
                'description',
                'can_be_generated',
                'legal_basis_json',
            ]);

        return response()->json([
            'data' => $documentTypes,
        ]);
    }
}
