<?php

namespace App\Application\Tasks\UseCases;

use App\Application\Tasks\Commands\AssignTaskCommand;
use App\Domain\Tasks\Entities\Task;
use App\Domain\Tasks\Repositories\TaskRepositoryInterface;
use Illuminate\Validation\ValidationException;

class AssignTaskUseCase
{
    public function __construct(
        private TaskRepositoryInterface $tasks,
    ) {}

    public function handle(AssignTaskCommand $command): Task
    {
        $task = $this->tasks->findById($command->taskId);

        if (! $task) {
            throw ValidationException::withMessages([
                'task_id' => ['Задача не найдена.'],
            ]);
        }

        return $this->tasks->update($task, [
            'assigned_to' => $command->assignedTo,
        ]);
    }
}
