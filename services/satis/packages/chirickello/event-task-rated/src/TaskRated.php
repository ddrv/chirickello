<?php

declare(strict_types=1);

namespace Chirickello\Package\Event\TaskRated;

use Chirickello\Package\Event\BaseEvent\BaseEvent;

class TaskRated extends BaseEvent
{
    public function jsonSerialize(): array
    {
        return [
            'event' => 'task.rated',
            'data' => [],
        ];
    }
}
