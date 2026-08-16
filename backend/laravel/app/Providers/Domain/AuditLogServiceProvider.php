<?php

namespace App\Providers\Domain;

use App\Domain\Audit\Repositories\AuditLogRepositoryInterface;
use App\Domain\Audit\Services\AuditService;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentAuditLogRepository;
use Illuminate\Support\ServiceProvider;

class AuditLogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AuditLogRepositoryInterface::class,
            EloquentAuditLogRepository::class
        );
        $this->app->singleton(
            AuditService::class,
        );
    }
    public function boot(): void
    {
        //
    }
}
