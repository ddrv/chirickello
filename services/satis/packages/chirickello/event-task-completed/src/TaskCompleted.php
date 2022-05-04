<?php

declare(strict_types=1);

namespace Chirickello\Package\Event\TaskCompleted;

use Chirickello\Package\Event\BaseEvent\BaseEvent;
use DateTimeImmutable;

class TaskCompleted extends BaseEvent
{
    private string $taskId;
    private string $employeeUserId;
    private string $taskDescription;
    private DateTimeImmutable $time;

    public function __construct(
        string $taskId,
        string $assignedUserId,
        string $taskDescription,
        DateTimeImmutable $time
    ) {
        $this->taskId = $taskId;
        $this->employeeUserId = $assignedUserId;
        $this->taskDescription = $taskDescription;
        $this->time = $time;
    }

    public function getTaskId(): string
    {
        return $this->taskId;
    }

    public function getEmployeeUserId(): string
    {
        return $this->employeeUserId;
    }

    public function getTaskDescription(): string
    {
        return $this->taskDescription;
    }

    public function getTime(): DateTimeImmutable
    {
        return $this->time;
    }

    public function jsonSerialize(): array
    {
        return [
            'event' => 'task.completed',
            'data' => [
                'taskId' => $this->taskId,
                'employeeUserId' => $this->employeeUserId,
                'taskDescription' => $this->taskDescription,
                'time' => $this->time->format('Y-m-d\TH:i:s')
            ],
        ];
    }
}
