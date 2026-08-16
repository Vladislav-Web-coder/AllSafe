<?php

namespace App\Providers\Domain;

use App\Domain\Knowledge\Repositories\LegalChunkRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentLegalChunkRepository;
use Illuminate\Support\ServiceProvider;

class KnowledgeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            LegalChunkRepositoryInterface::class,
            EloquentLegalChunkRepository::class
        );
    }
    public function boot(): void
    {
        //
    }
}
