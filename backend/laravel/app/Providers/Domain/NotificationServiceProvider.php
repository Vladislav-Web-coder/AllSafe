<?php

namespace App\Providers\Domain;

use App\Domain\Notifications\Repositories\NotificationRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentNotificationRepository;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            NotificationRepositoryInterface::class,
            EloquentNotificationRepository::class
        );
    }
    public function boot(): void
    {
        //
    }
}
