<?php

namespace App\Interfaces\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanUploadDocumentsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $role = $request->attributes->get('currentOrganizationRole');

        if (! $role || ! $role->canUploadDocuments()) {
            abort(403, 'Недостаточно прав для загрузки документов.');
        }

        return $next($request);
    }
}
