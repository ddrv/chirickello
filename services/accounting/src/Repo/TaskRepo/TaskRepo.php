<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Repo\TaskRepo;

use Chirickello\Accounting\Entity\Task;
use Chirickello\Accounting\Exception\StorageException;
use Chirickello\Accounting\Exception\TaskAlreadyExistsException;
use Chirickello\Accounting\Exception\TaskNotFoundException;

interface TaskRepo
{
    /**
     * @param string $id
     * @return Task
     * @throws TaskNotFoundException
     */
    public function getById(string $id): Task;

    /**
     * @param Task $task
     * @return void
     * @throws StorageException
     * @throws TaskAlreadyExistsException
     */
    public function save(Task $task): void;
}
