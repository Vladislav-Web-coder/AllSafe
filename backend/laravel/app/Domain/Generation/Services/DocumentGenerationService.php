<?php

namespace App\Domain\Generation\Services;

use App\Domain\Documents\Entities\Document;
use App\Domain\Documents\Entities\DocumentVersion;
use App\Domain\Documents\Enums\DocumentSource;
use App\Domain\Documents\Enums\DocumentStatus;
use App\Domain\Documents\Repositories\DocumentRepositoryInterface;
use App\Domain\Documents\Repositories\DocumentVersionRepositoryInterface;
use App\Domain\Generation\Entities\DocumentTemplate;
use App\Domain\Generation\Entities\GeneratedDocument;
use App\Domain\Generation\Entities\GenerationRun;
use App\Domain\Generation\Enums\GenerationStatus;
use App\Domain\Generation\Repositories\GenerationRunRepositoryInterface;
use App\Domain\Organizations\Entities\Organization;
use App\Domain\Profiles\Entities\OrganizationProfile;
use App\Infrastructure\Storage\DocumentFileStorageInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DocumentGenerationService
{
    public function __construct(
        private DocumentContentGenerator $contentGenerator,
        private DocxGenerator $docxGenerator,
        private DocumentFileStorageInterface $fileStorage,
        private DocumentRepositoryInterface $documents,
        private DocumentVersionRepositoryInterface $versions,
        private GenerationRunRepositoryInterface $runs,
    ) {}

    public function generate(
        GenerationRun $run,
        DocumentTemplate $template,
        Organization $organization,
        ?OrganizationProfile $profile,
        int $userId,
    ): Document {
        try {
            $this->runs->update($run, [
                'status' => GenerationStatus::Processing,
                'started_at' => now(),
            ]);

            // Генерируем контент через LLM
            $generated = $this->contentGenerator->generate($template, $organization, $profile);

            $content = $generated['content'] ?? '';
            $sections = $generated['sections'] ?? [];

            if (empty($content) && empty($sections)) {
                throw new \RuntimeException('LLM не вернул контент документа.');
            }

            // Сохраняем сгенерированный контент
            GeneratedDocument::query()->create([
                'generation_run_id' => $run->id,
                'content' => $content,
                'sections_json' => $sections,
                'metadata_json' => [
                    'template_code' => $template->code,
                    'model' => config('ai.client'),
                ],
            ]);

            // Генерируем DOCX файл
            $documentTitle = $template->name;

            $tempPath = $this->docxGenerator->generate(
                title: $documentTitle,
                sections: $sections,
                organizationName: $organization->name,
            );

            // Считаем hash из временного файла ДО загрузки в MinIO
            $fileHash = hash_file('sha256', $tempPath);
            $fileSize = filesize($tempPath);

            // Загружаем файл в MinIO
            $disk = Storage::disk('minio');

            $directory = sprintf(
                'organizations/%d/generated',
                $organization->id
            );

            $safeFileName = $this->sanitizeFileName($template->name);
            $fileName = $safeFileName . '.docx';

            $filePath = $disk->putFileAs(
                $directory,
                new \Illuminate\Http\File($tempPath),
                $fileName
            );

            // Удаляем временный файл
            @unlink($tempPath);

            // Создаём документ и версию в транзакции
            $document = DB::connection('pgsql_core')->transaction(function () use (
                $organization,
                $template,
                $filePath,
                $fileName,
                $fileHash,
                $fileSize,
                $userId,
            ) {
                $document = $this->documents->create([
                    'organization_id' => $organization->id,
                    'document_type_id' => $template->document_type_id,
                    'title' => $template->name,
                    'status' => DocumentStatus::Uploaded,
                    'source' => DocumentSource::Generated,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);

                $version = $this->versions->create([
                    'document_id' => $document->id,
                    'version_number' => 1,
                    'source' => DocumentSource::Generated,
                    'file_path' => $filePath,
                    'file_name' => $fileName,
                    'file_size' => $fileSize,
                    'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'file_hash' => $fileHash,
                    'storage_disk' => 'minio',
                    'created_by' => $userId,
                ]);

                $this->documents->update($document, [
                    'current_version_id' => $version->id,
                ]);

                return $document;
            });

            // Обновляем статус запуска
            $this->runs->update($run, [
                'status' => GenerationStatus::Completed,
                'document_id' => $document->id,
                'finished_at' => now(),
            ]);

            return $document;
        } catch (Throwable $e) {
            $this->runs->update($run, [
                'status' => GenerationStatus::Failed,
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            throw $e;
        }
    }
    /**
     * Очищает имя файла от недопустимых символов.
     */
    private function sanitizeFileName(string $name): string
    {
        $name = preg_replace('/[<>:"\/\\\\|?*]/', '', $name);

        $name = str_replace(' ', '_', $name);

        $name = mb_substr($name, 0, 100);

        return $name;
    }
}
