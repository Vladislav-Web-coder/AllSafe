<?php

namespace App\Application\Generation\UseCases;

use App\Application\Generation\Commands\StartDocumentGenerationCommand;
use App\Domain\Generation\Entities\GenerationRun;
use App\Domain\Generation\Enums\GenerationStatus;
use App\Domain\Generation\Repositories\DocumentTemplateRepositoryInterface;
use App\Domain\Generation\Repositories\GenerationRunRepositoryInterface;
use App\Jobs\GenerateDocumentJob;
use Illuminate\Validation\ValidationException;

class StartDocumentGenerationUseCase
{
    public function __construct(
        private GenerationRunRepositoryInterface $runs,
        private DocumentTemplateRepositoryInterface $templates,
    ) {}

    public function handle(StartDocumentGenerationCommand $command): GenerationRun
    {
        $template = $this->templates->findById($command->documentTemplateId);

        if (! $template || ! $template->is_active) {
            throw ValidationException::withMessages([
                'document_template_id' => ['Шаблон документа не найден или неактивен.'],
            ]);
        }

        $run = $this->runs->create([
            'organization_id' => $command->organizationId,
            'document_template_id' => $template->id,
            'status' => GenerationStatus::Pending,
            'created_by' => $command->userId,
        ]);

        GenerateDocumentJob::dispatch($run->id, $command->userId)
            ->onQueue('documents.generation');

        return $run;
    }
}
