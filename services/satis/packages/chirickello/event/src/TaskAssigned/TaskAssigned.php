<?php

declare(strict_types=1);

namespace Chirickello\Package\Event\TaskAssigned;

use Chirickello\Package\Event\BaseEvent;
use DateTimeImmutable;
use DateTimeZone;

class TaskAssigned extends BaseEvent
{
    private string $taskId;
    private string $assignedUserId;
    private DateTimeImmutable $assignTime;

    public function __construct(
        string            $taskId,
        string            $assignedUserId,
        DateTimeImmutable $assignTime
    ) {
        $this->taskId = $taskId;
        $this->assignedUserId = $assignedUserId;
        $this->assignTime = $assignTime;
    }

    public function getEventName(): string
    {
        return 'task.assigned';
    }

    public function getTaskId(): string
    {
        return $this->taskId;
    }

    public function getAssignedUserId(): string
    {
        return $this->assignedUserId;
    }

    public function setAssignTime(DateTimeImmutable $assignTime): void
    {
        $this->assignTime = $assignTime;
    }

    public function jsonDataSerialize(): object
    {
        return (object)[
            'taskId' => $this->taskId,
            'assignedUserId' => $this->assignedUserId,
            'assignTime' => $this->assignTime->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.vP'),
        ];
    }
}
