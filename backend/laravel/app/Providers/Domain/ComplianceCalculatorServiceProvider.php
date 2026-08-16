<?php

namespace App\Providers\Domain;

use App\Domain\Compliance\Services\ComplianceCalculator;
use Illuminate\Support\ServiceProvider;

class ComplianceCalculatorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            ComplianceCalculator::class,
        );
    }
    public function boot(): void
    {
        //
    }
}
