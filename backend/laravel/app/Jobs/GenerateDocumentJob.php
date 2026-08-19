<?php

namespace App\Jobs;

use App\Domain\Generation\Repositories\DocumentTemplateRepositoryInterface;
use App\Domain\Generation\Repositories\GenerationRunRepositoryInterface;
use App\Domain\Generation\Services\DocumentGenerationService;
use App\Domain\Notifications\Services\NotificationService;
use App\Domain\Organizations\Entities\Organization;
use App\Domain\Profiles\Repositories\OrganizationProfileRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateDocumentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(
        public int $generationRunId,
        public int $userId,
    ) {}

    public function handle(
        GenerationRunRepositoryInterface $runs,
        DocumentTemplateRepositoryInterface $templates,
        OrganizationProfileRepositoryInterface $profiles,
        DocumentGenerationService $generationService,
        NotificationService $notificationService
    ): void {
        $run = $runs->findById($this->generationRunId);

        if (! $run) {
            return;
        }

        $template = $templates->findById($run->document_template_id);

        if (! $template) {
            $runs->update($run, [
                'status' => \App\Domain\Generation\Enums\GenerationStatus::Failed,
                'error_message' => 'Шаблон документа не найден.',
                'finished_at' => now(),
            ]);

            return;
        }

        $organization = Organization::query()->find($run->organization_id);

        if (! $organization) {
            $runs->update($run, [
                'status' => \App\Domain\Generation\Enums\GenerationStatus::Failed,
                'error_message' => 'Организация не найдена.',
                'finished_at' => now(),
            ]);

            return;
        }

        $profile = $profiles->findByOrganizationId($organization->id);

        $generationService->generate(
            run: $run,
            template: $template,
            organization: $organization,
            profile: $profile,
            userId: $this->userId,
        );

        $notificationService->notify(
            userId: $run->created_by,
            organizationId: $organization->id,
            type: 'document_generated',
            title: 'Документ сгенерирован',
            message: "Документ \"{$template->name}\" успешно сгенерирован.",
            linkType: 'document',
        );
    }
}
