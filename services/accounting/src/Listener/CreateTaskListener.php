<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Listener;

use Chirickello\Accounting\Entity\Task;
use Chirickello\Accounting\Exception\TaskAlreadyExistsException;
use Chirickello\Accounting\Repo\TaskRepo\TaskRepo;
use Chirickello\Package\Event\TaskAdded\TaskAdded;
use Chirickello\Package\Event\TaskRated\TaskRated;
use Psr\EventDispatcher\EventDispatcherInterface;

class CreateTaskListener
{
    private TaskRepo $taskRepo;

    public function __construct(TaskRepo $taskRepo)
    {
        $this->taskRepo = $taskRepo;
    }

    public function __invoke(object $event, string $eventName, EventDispatcherInterface $eventDispatcher)
    {
        if (!$event instanceof TaskAdded) {
            return;
        }
        $tax = rand(1000, 2000);
        $cost = rand(2000, 4000);
        $task = new Task($event->getTaskId(), $event->getTaskTitle(), $tax, $cost);

        try {
            $this->taskRepo->save($task);
        } catch (TaskAlreadyExistsException $exception) {
        }
    }
}
