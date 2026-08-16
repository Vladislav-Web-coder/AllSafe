<?php

namespace App\Providers\Domain;

use App\Infrastructure\Parsing\DocumentTextExtractor;
use App\Infrastructure\Parsing\DocumentTextExtractorInterface;
use Illuminate\Support\ServiceProvider;

class ParsingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            DocumentTextExtractorInterface::class,
            DocumentTextExtractor::class
        );
    }
    public function boot(): void
    {
        //
    }
}
