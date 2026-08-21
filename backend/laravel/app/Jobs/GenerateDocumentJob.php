<?php

namespace App\Jobs;

use App\Domain\Generation\Enums\GenerationStatus;
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
use Illuminate\Support\Facades\Log;
use Throwable;

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
        NotificationService $notificationService,
    ): void {
        $run = $runs->findById($this->generationRunId);

        if (! $run) {
            Log::warning('GenerateDocumentJob: run not found', [
                'run_id' => $this->generationRunId,
            ]);
            return;
        }

        try {
            $template = $templates->findById($run->document_template_id);

            if (! $template) {
                $this->failRun($runs, $run, 'Шаблон документа не найден.');
                return;
            }

            $organization = Organization::query()->find($run->organization_id);

            if (! $organization) {
                $this->failRun($runs, $run, 'Организация не найдена.');
                return;
            }

            $profile = $profiles->findByOrganizationId($organization->id);

            Log::info('GenerateDocumentJob: starting generation', [
                'run_id' => $run->id,
                'template' => $template->code,
                'organization_id' => $organization->id,
                'has_profile' => $profile !== null,
            ]);

            $document = $generationService->generate(
                run: $run,
                template: $template,
                organization: $organization,
                profile: $profile,
                userId: $this->userId,
            );

            Log::info('GenerateDocumentJob: generation completed', [
                'run_id' => $run->id,
                'document_id' => $document->id,
            ]);

            // Уведомление об успехе — только если генерация прошла
            $notificationService->notify(
                userId: $run->created_by,
                organizationId: $organization->id,
                type: 'document_generated',
                title: 'Документ сгенерирован',
                message: "Документ «{$template->name}» успешно сгенерирован и добавлен в список документов.",
                linkType: 'document',
                linkId: $document->id,
            );

        } catch (Throwable $e) {
            Log::error('GenerateDocumentJob: generation failed', [
                'run_id' => $this->generationRunId,
                'error' => $e->getMessage(),
            ]);

            // Обновляем статус запуска
            $runs->update($run, [
                'status' => GenerationStatus::Failed,
                'error_message' => mb_substr($e->getMessage(), 0, 1000),
                'finished_at' => now(),
            ]);

            // Уведомление об ошибке
            $organization = Organization::query()->find($run->organization_id);

            if ($organization) {
                $notificationService->notify(
                    userId: $run->created_by,
                    organizationId: $organization->id,
                    type: 'generation_failed',
                    title: 'Ошибка генерации документа',
                    message: "Не удалось сгенерировать документ: {$e->getMessage()}",
                    linkType: null,
                    linkId: null,
                );
            }

            report($e);
        }
    }

    private function failRun(GenerationRunRepositoryInterface $runs, $run, string $message): void {
        $runs->update($run, [
            'status' => GenerationStatus::Failed,
            'error_message' => $message,
            'finished_at' => now(),
        ]);

        Log::warning('GenerateDocumentJob: ' . $message, [
            'run_id' => $run->id,
        ]);
    }
}
