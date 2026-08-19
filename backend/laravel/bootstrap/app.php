<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'organization.access' => \Interfaces\Http\Middleware\EnsureOrganizationAccessMiddleware::class,
            'organization.manage' => \Interfaces\Http\Middleware\EnsureOrganizationManageMiddleware::class,
            'organization.members.manage' => \Interfaces\Http\Middleware\EnsureOrganizationManageMembersMiddleware::class,
            'organization.documents.upload' => \App\Interfaces\Http\Middleware\EnsureCanUploadDocumentsMiddleware::class,
            'document.access' => \App\Interfaces\Http\Middleware\EnsureDocumentAccessMiddleware::class,
            'organization.documents.analyze' => \App\Interfaces\Http\Middleware\EnsureCanAnalyzeDocumentsMiddleware::class,
            'organization.owner' => \App\Interfaces\Http\Middleware\EnsureOrganizationOwnerMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
