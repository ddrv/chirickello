<?php

declare(strict_types=1);

namespace Chirickello\Package\Event\TaskRated;

use Chirickello\Package\Event\BaseEvent;

class TaskRated extends BaseEvent
{
    public function getEventName(): string
    {
        return 'task.rated';
    }

    public function jsonDataSerialize(): object
    {
        return (object)[
        ];
    }
}
