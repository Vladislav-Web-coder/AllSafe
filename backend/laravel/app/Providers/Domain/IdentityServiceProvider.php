<?php

namespace App\Providers\Domain;

use App\Domain\Identity\Entities\PersonalAccessToken;
use App\Domain\Identity\Repositories\RefreshTokenRepositoryInterface;
use App\Domain\Identity\Services\EmailService;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentRefreshTokenRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository;
use Illuminate\Support\ServiceProvider;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use Laravel\Sanctum\Sanctum;

class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            UserRepositoryInterface::class,
            EloquentUserRepository::class
        );
        $this->app->bind(
            RefreshTokenRepositoryInterface::class,
            EloquentRefreshTokenRepository::class
        );
        $this->app->singleton(EmailService::class);
    }

    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }
}
