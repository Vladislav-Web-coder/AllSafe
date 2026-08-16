<?php

namespace App\Application\Tasks\UseCases;

use App\Application\Tasks\Commands\UpdateTaskStatusCommand;
use App\Domain\Tasks\Entities\Task;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Domain\Tasks\Repositories\TaskRepositoryInterface;
use App\Domain\Tasks\Services\TaskStatusTransition;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateTaskStatusUseCase
{
    public function __construct(
        private TaskRepositoryInterface $tasks,
    ) {}

    public function handle(UpdateTaskStatusCommand $command): Task
    {
        $task = $this->tasks->findById($command->taskId);

        if (! $task) {
            throw ValidationException::withMessages([
                'task_id' => ['Задача не найдена.'],
            ]);
        }

        if (! TaskStatusTransition::canTransition($task->status, $command->newStatus)) {
            throw ValidationException::withMessages([
                'status' => [
                    "Недопустимый переход статуса из '{$task->status->label()}' в '{$command->newStatus->label()}'.",
                ],
            ]);
        }

        return DB::connection('pgsql_core')->transaction(function () use ($command, $task) {
            $updateData = [
                'status' => $command->newStatus,
            ];

            // При переходе в in_progress фиксируем начало
            if ($command->newStatus === TaskStatus::InProgress && ! $task->started_at) {
                $updateData['started_at'] = now();
            }

            // При завершении фиксируем время
            if ($command->newStatus === TaskStatus::Done) {
                $updateData['completed_at'] = now();
            }

            // При переоткрытии сбрасываем completed_at
            if ($command->newStatus === TaskStatus::InProgress && $task->status === TaskStatus::Done) {
                $updateData['completed_at'] = null;
            }

            return $this->tasks->update($task, $updateData);
        });
    }
}
