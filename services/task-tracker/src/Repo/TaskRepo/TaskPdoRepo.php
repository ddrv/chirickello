<?php

declare(strict_types=1);

namespace Chirickello\TaskTracker\Repo\TaskRepo;

use Chirickello\TaskTracker\Entity\Task;
use Chirickello\TaskTracker\Exception\StorageException;
use Chirickello\TaskTracker\Exception\TaskNotFoundException;
use Chirickello\TaskTracker\Repo\Paginator;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Throwable;

class TaskPdoRepo implements TaskRepo
{
    private const TIME_FORMAT = 'Y-m-d\TH:i:s';
    private PDO $db;
    private DateTimeZone $utc;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->utc = new DateTimeZone('UTC');
    }

    /**
     * @inheritDoc
     */
    public function getById(string $id): Task
    {
        $sql = 'SELECT * FROM tasks WHERE id = ?;';
        $st = $this->db->prepare($sql);
        $st->execute([$id]);
        $row = $st->fetch();
        if (empty($row)) {
            throw new TaskNotFoundException();
        }
        return $this->hydrateTask($row);
    }

    /**
     * @inheritDoc
     */
    public function getFiltered(TaskFilter $filter, ?Paginator $paginator = null): iterable
    {
        [$sql, $params] = $this->createSelectSql($filter, $paginator, false);
        $st = $this->db->prepare($sql);
        $st->execute($params);
        $result = [];
        while($row = $st->fetch()) {
            $result[] = $this->hydrateTask($row);
        }
        return $result;
    }

    public function countFiltered(TaskFilter $filter): int
    {
        [$sql, $params] = $this->createSelectSql($filter, null, true);
        $st = $this->db->prepare($sql);
        $st->execute($params);
        $row = $st->fetch();
        if (empty($row)) {
            return 0;
        }
        return (int)$row['tasks_quantity'];
    }

    /**
     * @inheritDoc
     */
    public function save(Task $task): void
    {
        $this->db->beginTransaction();
        try {
            if ($task->isNew()) {
                $this->create($task);
            } else {
                $this->update($task);
            }
            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw new StorageException('storage error', 0, $exception);
        }
        $task->flush();
    }

    private function create(Task $task): void
    {
        $id = $this->generateId();
        $sql = 'INSERT INTO tasks (id, author_id, assigned_to, description, is_completed, created_at) VALUES (?, ?, ?, ?, ?, ?);';
        $st = $this->db->prepare($sql);
        $st->execute([
            $id,
            $task->getAuthorId(),
            $task->getAssignedTo(),
            $task->getDescription(),
            (int)$task->isCompleted(),
            $task->getCreatedAt()->setTimezone($this->utc)->format(self::TIME_FORMAT),
        ]);
        $task->setId($id);
    }

    private function update(Task $task): void
    {
        $set = [];
        $params = [];
        if ($task->isAssignedToChanged()) {
            $set[] = 'assigned_to = ?';
            $params[] = $task->getAssignedTo();
        }
        if ($task->isCompletedChanged()) {
            $set[] = 'is_completed = ?';
            $params[] = (int)$task->isCompleted();
        }
        if (empty($set)) {
            return;
        }
        $params[] = $task->getId();
        $sql = 'UPDATE tasks SET ' . (implode(', ', $set)) . ' WHERE id = ?;';
        $st = $this->db->prepare($sql);
        $st->execute($params);
    }

    private function hydrateTask(array $row): Task
    {
        $task = new Task(
            $row['author_id'],
            $row['assigned_to'],
            $row['description'],
            DateTimeImmutable::createFromFormat(self::TIME_FORMAT, $row['created_at'], $this->utc),
            $row['id']
        );
        if (!empty((int)$row['is_completed'])) {
            $task->complete();
        }
        $task->flush();
        return $task;
    }

    private function generateId(): string
    {
        $st = $this->db->prepare('SELECT * FROM tasks WHERE id = ?;');
        do {
            $uuid = $this->generateUuid();
            $st->execute([$uuid]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
        } while (!empty($row));
        return $uuid;
    }

    private function generateUuid(): string
    {
        // https://www.php.net/manual/en/function.uniqid.php#94959
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,

            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }

    private function createSelectSql(TaskFilter $filter, ?Paginator $paginator, bool $isCount): array
    {
        $where = [];
        $params = [];
        $columns = $isCount ? 'COUNT(*) as tasks_quantity' : '*';
        $sql = 'SELECT ' . $columns . ' FROM tasks';
        if (!is_null($filter->isCompleted)) {
            $where[] = 'is_completed = ?';
            $params[] = (int)$filter->isCompleted;
        }
        if (!empty($where)) {
            $sql .= ' WHERE ' . (implode(' AND ', $where));
        }
        if ($isCount) {
            return [$sql . ';', $params];
        }
        $sql .= ' ORDER BY created_at' . $filter->reverse ? ' DESC' : ' ASC';
        if (!is_null($paginator)) {
            $offset = ($paginator->pageNum - 1) * $paginator->perPage;
            $sql .= ' LIMIT ' . $paginator->perPage . ' OFFSET ' . $offset;
        }
        return [$sql . ';', $params];
    }
}
