<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Repo\TaskRepo;

use Chirickello\Accounting\Entity\Task;
use Chirickello\Accounting\Exception\StorageException;
use Chirickello\Accounting\Exception\TaskAlreadyExistsException;
use Chirickello\Accounting\Exception\TaskNotFoundException;
use PDO;
use Throwable;

class TaskPdoRepo implements TaskRepo
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * @inheritDoc
     */
    public function getById(string $id): Task
    {
        $st = $this->db->prepare('SELECT * FROM tasks WHERE id = ?;');
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (empty($row)) {
            throw new TaskNotFoundException(sprintf('task with id %s not found', $id));
        }
        return $this->hydrateTask($row);
    }

    /**
     * @inheritDoc
     */
    public function save(Task $task): void
    {
        $this->db->beginTransaction();
        try {
            $id = $task->getId();
            try {
                $this->getById($id);
                throw new TaskAlreadyExistsException(sprintf('task %s already exists', $id));
            } catch (TaskNotFoundException $exception) {
            }
            $st = $this->db->prepare('INSERT INTO tasks (id, title, tax, cost) VALUES (?, ?, ?, ?);');
            $st->execute([
                $id,
                $task->getTitle(),
                $task->getTax(),
                $task->getCost(),
            ]);
            $this->db->commit();
        } catch (TaskAlreadyExistsException $exception) {
            $this->db->rollBack();
            throw $exception;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw new StorageException('storage error', 0, $exception);
        }
    }

    private function hydrateTask(array $row): Task
    {
        return new Task(
            $row['id'],
            $row['title'],
            (int)$row['tax'],
            (int)$row['cost']
        );
    }
}
