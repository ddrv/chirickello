<?php

declare(strict_types=1);

namespace Chirickello\TaskTracker\Repo\TaskRepo;

use Chirickello\TaskTracker\Entity\Task;
use Chirickello\TaskTracker\Exception\StorageException;
use Chirickello\TaskTracker\Exception\TaskNotFoundException;
use Chirickello\TaskTracker\Repo\Paginator;

interface TaskRepo
{
    /**
     * @param string $id
     * @return Task
     * @throws TaskNotFoundException
     */
    public function getById(string $id): Task;

    /**
     * @return Task[]
     */
    public function getFiltered(TaskFilter $filter, ?Paginator $paginator = null): iterable;

    public function countFiltered(TaskFilter $filter): int;

    /**
     * @param Task $task
     * @return void
     * @throws StorageException
     */
    public function save(Task $task): void;
}
