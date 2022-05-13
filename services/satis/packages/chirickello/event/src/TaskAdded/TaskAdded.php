<?php

declare(strict_types=1);

namespace Chirickello\Package\Event\TaskAdded;

use Chirickello\Package\Event\BaseEvent;

class TaskAdded extends BaseEvent
{
    private string $taskId;
    private string $taskTitle;

    public function __construct(
        string $taskId,
        string $taskTitle
    ) {
        $this->taskId = $taskId;
        $this->taskTitle = $taskTitle;
    }

    public function getEventName(): string
    {
        return 'task.added';
    }

    public function getTaskId(): string
    {
        return $this->taskId;
    }

    public function getTaskTitle(): string
    {
        return $this->taskTitle;
    }

    public function jsonDataSerialize(): object
    {
        return (object)[
            'taskId' => $this->taskId,
            'taskTitle' => $this->taskTitle,
        ];
    }
}
