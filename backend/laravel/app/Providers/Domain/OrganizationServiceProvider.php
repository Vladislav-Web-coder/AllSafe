<?php

namespace App\Providers\Domain;

use App\Domain\Organizations\Repositories\OrganizationInvitationRepositoryInterface;
use App\Domain\Organizations\Repositories\OrganizationMemberRepositoryInterface;
use App\Domain\Organizations\Repositories\OrganizationRepositoryInterface;
use App\Domain\Profiles\Repositories\OrganizationProfileRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrganizationInvitationRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrganizationMemberRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrganizationProfileRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrganizationRepository;
use Illuminate\Support\ServiceProvider;

class OrganizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
       $this->app->bind(
           OrganizationRepositoryInterface::class,
           EloquentOrganizationRepository::class
       );
       $this->app->bind(
           OrganizationMemberRepositoryInterface::class,
           EloquentOrganizationMemberRepository::class
       );
       $this->app->bind(
           OrganizationProfileRepositoryInterface::class,
           EloquentOrganizationProfileRepository::class
       );
       $this->app->bind(
           OrganizationInvitationRepositoryInterface::class,
           EloquentOrganizationInvitationRepository::class
       );
    }

    public function boot(): void
    {
        //
    }
}
