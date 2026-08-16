<?php

namespace App\Providers\Domain;

use App\Domain\Tasks\Repositories\TaskCommentRepositoryInterface;
use App\Domain\Tasks\Repositories\TaskRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentTaskCommentRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentTaskRepository;
use Illuminate\Support\ServiceProvider;

class TaskServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            TaskRepositoryInterface::class,
            EloquentTaskRepository::class
        );
        $this->app->bind(
            TaskCommentRepositoryInterface::class,
            EloquentTaskCommentRepository::class
        );
    }
    public function boot(): void
    {
        //
    }
}
