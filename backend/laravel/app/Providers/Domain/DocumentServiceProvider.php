<?php

namespace App\Providers\Domain;

use App\Domain\Documents\Repositories\DocumentRepositoryInterface;
use App\Domain\Documents\Repositories\DocumentTypeRepositoryInterface;
use App\Domain\Documents\Repositories\DocumentVersionRepositoryInterface;
use App\Domain\Generation\Repositories\DocumentTemplateRepositoryInterface;
use App\Domain\Generation\Repositories\GenerationRunRepositoryInterface;
use App\Domain\Generation\Services\DocumentGenerationService;
use App\Domain\Requirements\Repositories\DocumentRequirementRuleRepositoryInterface;
use App\Domain\Requirements\Services\RequiredDocumentsCalculator;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentDocumentRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentDocumentRequirementRuleRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentDocumentTemplateRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentDocumentTypeRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentDocumentVersionRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentGenerationRunRepository;
use App\Infrastructure\Storage\DocumentFileStorageInterface;
use App\Infrastructure\Storage\Minio\MinioDocumentFileStorage;
use Illuminate\Support\ServiceProvider;

class DocumentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            DocumentRepositoryInterface::class,
            EloquentDocumentRepository::class
        );
        $this->app->bind(
            DocumentTypeRepositoryInterface::class,
            EloquentDocumentTypeRepository::class
        );
        $this->app->bind(
            DocumentFileStorageInterface::class,
            MinioDocumentFileStorage::class
        );
        $this->app->bind(
            DocumentVersionRepositoryInterface::class,
            EloquentDocumentVersionRepository::class
        );
        $this->app->bind(
            DocumentRequirementRuleRepositoryInterface::class,
            EloquentDocumentRequirementRuleRepository::class
        );
        $this->app->singleton(
            RequiredDocumentsCalculator::class
        );
        $this->app->bind(
            DocumentTemplateRepositoryInterface::class,
            EloquentDocumentTemplateRepository::class
        );
        $this->app->bind(
            GenerationRunRepositoryInterface::class,
            EloquentGenerationRunRepository::class
        );
        $this->app->singleton(
            DocumentGenerationService::class,
        );
    }
    public function boot()
    {
        //
    }
}
