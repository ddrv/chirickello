<?php

declare(strict_types=1);

namespace Chirickello\Package\Event\TaskRated;

use Chirickello\Package\Event\BaseEvent;

class TaskRated extends BaseEvent
{
    public function jsonSerialize(): object
    {
        return (object)[
            'event' => 'task.rated',
            'data' => (object)[],
        ];
    }
}
