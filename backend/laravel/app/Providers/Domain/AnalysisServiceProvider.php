<?php

namespace App\Providers\Domain;

use App\Domain\Analysis\Repositories\AnalysisRunRepositoryInterface;
use App\Domain\Analysis\Repositories\DocumentIssueRepositoryInterface;
use App\Domain\Issues\Repositories\IssueCommentRepositoryInterface;
use App\Domain\Issues\Repositories\IssueHistoryRepositoryInterface;
use App\Infrastructure\AI\AiClientInterface;
use App\Infrastructure\AI\FakeAiClient;
use App\Infrastructure\AI\LlamaCppClient;
use App\Infrastructure\AI\OllamaClient;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentAnalysisRunRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentDocumentIssueRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentIssueCommentRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentIssueHistoryRepository;
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
        $this->app->bind(AiClientInterface::class, function ($app) {
            return match (config('ai.client')) {
                'ollama' => $app->make(OllamaClient::class),
                'llama_cpp' => $app->make(LlamaCppClient::class),
                default => $app->make(FakeAiClient::class),
            };
        });
        $this->app->bind(
            IssueCommentRepositoryInterface::class,
            EloquentIssueCommentRepository::class
        );
        $this->app->bind(
            IssueHistoryRepositoryInterface::class,
            EloquentIssueHistoryRepository::class
        );
    }
    public function boot()
    {
        //
    }
}
