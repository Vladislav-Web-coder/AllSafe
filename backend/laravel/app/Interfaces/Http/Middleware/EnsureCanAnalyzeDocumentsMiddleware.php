<?php

namespace App\Interfaces\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanAnalyzeDocumentsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $role = $request->attributes->get('currentOrganizationRole');

        if (! $role || ! $role->canAnalyzeDocuments()) {
            abort(403, 'Недостаточно прав для анализа документов.');
        }

        return $next($request);
    }
}
