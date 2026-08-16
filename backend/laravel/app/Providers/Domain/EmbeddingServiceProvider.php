<?php

namespace App\Providers\Domain;

use App\Infrastructure\Embeddings\EmbeddingServiceInterface;
use App\Infrastructure\Embeddings\FakeEmbeddingService;
use Illuminate\Support\ServiceProvider;

class EmbeddingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            EmbeddingServiceInterface::class,
            FakeEmbeddingService::class,
        );
    }
    public function boot(): void
    {
        //
    }
}
