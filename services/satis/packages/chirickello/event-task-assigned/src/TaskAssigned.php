<?php

declare(strict_types=1);

namespace Chirickello\Package\Event\TaskAssigned;

use Chirickello\Package\Event\BaseEvent\BaseEvent;

class TaskAssigned extends BaseEvent
{
    public function jsonSerialize(): array
    {
        return [
            'event' => 'task.assigned',
            'data' => [],
        ];
    }
}
