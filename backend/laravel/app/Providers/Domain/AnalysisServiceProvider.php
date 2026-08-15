<?php

namespace App\Providers\Domain;

use App\Domain\Analysis\Repositories\AnalysisRunRepositoryInterface;
use App\Domain\Analysis\Repositories\DocumentIssueRepositoryInterface;
use App\Infrastructure\AI\AiClientInterface;
use App\Infrastructure\AI\FakeAiClient;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentAnalysisRunRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentDocumentIssueRepository;
use Illuminate\Support\ServiceProvider;

class AnalysisServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AnalysisRunRepositoryInterface::class,
            EloquentAnalysisRunRepository::class
        );
        $this->app->bind(
            DocumentIssueRepositoryInterface::class,
            EloquentDocumentIssueRepository::class
        );
        $this->app->bind(
            AiClientInterface::class,
            FakeAiClient::class
        );
    }
    public function boot()
    {
        //
    }
}
