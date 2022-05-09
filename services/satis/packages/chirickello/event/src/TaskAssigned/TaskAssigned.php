<?php

declare(strict_types=1);

namespace Chirickello\Package\Event\TaskAssigned;

use Chirickello\Package\Event\BaseEvent;
use DateTimeImmutable;

class TaskAssigned extends BaseEvent
{
    private string $taskId;
    private string $assignedUserId;
    private DateTimeImmutable $time;

    public function __construct(
        string $taskId,
        string $assignedUserId,
        DateTimeImmutable $time
    ) {
        $this->taskId = $taskId;
        $this->assignedUserId = $assignedUserId;
        $this->time = $time;
    }

    public function getTaskId(): string
    {
        return $this->taskId;
    }

    public function getAssignedUserId(): string
    {
        return $this->assignedUserId;
    }

    public function setTime(DateTimeImmutable $time): void
    {
        $this->time = $time;
    }

    public function jsonSerialize(): object
    {
        return (object)[
            'event' => 'task.assigned',
            'data' => (object)[
                'taskId' => $this->taskId,
                'assignedUserId' => $this->assignedUserId,
                'time' => $this->time->format('Y-m-d\TH:i:s'),
            ],
        ];
    }
}
