<?php

namespace App\Interfaces\Http\Middleware;

use App\Domain\Documents\Entities\Document;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDocumentAccessMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $organization = $request->attributes->get('currentOrganization');

        if (! $organization) {
            abort(403, 'Текущая организация не определена.');
        }

        $documentId = (int) $request->route('documentId');

        if (! $documentId) {
            abort(404, 'Документ не найден.');
        }

        $document = Document::query()
            ->where('id', $documentId)
            ->where('organization_id', $organization->id)
            ->first();

        if (! $document) {
            abort(404, 'Документ не найден.');
        }

        $request->attributes->set('currentDocument', $document);

        return $next($request);
    }
}
