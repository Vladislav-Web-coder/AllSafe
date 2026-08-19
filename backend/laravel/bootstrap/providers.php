<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Domain\IdentityServiceProvider::class,
    App\Providers\Domain\OrganizationServiceProvider::class,
    App\Providers\Domain\DocumentServiceProvider::class,
    App\Providers\Domain\AnalysisServiceProvider::class,
    App\Providers\Domain\ParsingServiceProvider::class,
    App\Providers\Domain\KnowledgeServiceProvider::class,
    App\Providers\Domain\EmbeddingServiceProvider::class,
    App\Providers\Domain\TaskServiceProvider::class,
    App\Providers\Domain\ComplianceCalculatorServiceProvider::class,
    App\Providers\Domain\AuditLogServiceProvider::class,
    App\Providers\Domain\NotificationServiceProvider::class
];
