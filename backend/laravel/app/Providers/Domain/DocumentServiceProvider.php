<?php

namespace App\Providers\Domain;

use App\Domain\Documents\Repositories\DocumentRepositoryInterface;
use App\Domain\Documents\Repositories\DocumentTypeRepositoryInterface;
use App\Domain\Documents\Repositories\DocumentVersionRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentDocumentRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentDocumentTypeRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentDocumentVersionRepository;
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
    }
    public function boot()
    {
        //
    }
}
