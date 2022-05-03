<?php

declare(strict_types=1);

namespace Chirickello\Package\Event\TaskCompleted;

use Chirickello\Package\Event\BaseEvent\BaseEvent;

class TaskCompleted extends BaseEvent
{
    public function jsonSerialize(): array
    {
        return [
            'event' => 'task.completed',
            'data' => [],
        ];
    }
}
