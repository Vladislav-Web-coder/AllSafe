<?php

namespace App\Providers\Domain;

use App\Domain\Knowledge\Services\LegalSearchService;
use App\Infrastructure\Embeddings\EmbeddingServiceInterface;
use App\Infrastructure\Embeddings\FakeEmbeddingService;
use App\Infrastructure\Embeddings\HttpEmbeddingService;
use Illuminate\Support\ServiceProvider;

class EmbeddingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            EmbeddingServiceInterface::class,
            HttpEmbeddingService::class
        );
        $this->app->singleton(LegalSearchService::class);
    }
    public function boot(): void
    {
        //
    }
}
