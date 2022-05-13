<?php

declare(strict_types=1);

namespace Chirickello\Package\Event\TaskCompleted;

use Chirickello\Package\Event\BaseEvent;
use DateTimeImmutable;
use DateTimeZone;

class TaskCompleted extends BaseEvent
{
    private string $taskId;
    private string $employeeUserId;
    private DateTimeImmutable $completionTime;

    public function __construct(
        string            $taskId,
        string            $employeeUserId,
        DateTimeImmutable $completionTime
    ) {
        $this->taskId = $taskId;
        $this->employeeUserId = $employeeUserId;
        $this->completionTime = $completionTime;
    }

    public function getTaskId(): string
    {
        return $this->taskId;
    }

    public function getEmployeeUserId(): string
    {
        return $this->employeeUserId;
    }

    public function getCompletionTime(): DateTimeImmutable
    {
        return $this->completionTime;
    }

    public function getEventName(): string
    {
        return 'task.completed';
    }

    public function jsonDataSerialize(): object
    {
        return (object)[
            'taskId' => $this->taskId,
            'employeeUserId' => $this->employeeUserId,
            'completionTime' => $this->completionTime->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.vP'),
        ];
    }
}
