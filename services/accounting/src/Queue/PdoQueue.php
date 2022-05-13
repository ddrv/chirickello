<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Queue;

use Chirickello\Package\Timer\TimerInterface;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Throwable;

class PdoQueue implements Queue
{
    private PDO $db;
    private TimerInterface $timer;
    private DateTimeZone $utc;

    public function __construct(PDO $db, TimerInterface $timer)
    {
        $this->db = $db;
        $this->timer = $timer;
        $this->utc = new DateTimeZone('UTC');
    }

    public function push(string $queue, string $message, DateTimeImmutable $deferredTo): void
    {
        $this->db->beginTransaction();
        try {
            $query1 = 'INSERT INTO queue (name, message, try_after) VALUES (?, ?, ?);';
            $params1 = [
                $queue,
                $message,
                $deferredTo->setTimezone($this->utc)->format('Y-m-d H:i:s'),
            ];

            $this->db->prepare($query1)->execute($params1);
            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
        }
    }

    public function pull(string $queue): ?string
    {
        $this->db->beginTransaction();
        try {
            $now = $this->timer->now()->setTimezone($this->utc);
            $query1 = 'SELECT id, message FROM queue WHERE name = ? AND try_after <= ? ORDER BY try_after, id LIMIT 1;'
            ;
            $params1 = [$queue, $now->format('Y-m-d H:i:s')];
            $st1 = $this->db->prepare($query1);
            $st1->execute($params1);
            $row1 = $st1->fetch(PDO::FETCH_ASSOC);
            if (empty($row1)) {
                $this->db->commit();
                return null;
            }
            $id = (int)$row1['id'];

            $query2 = 'DELETE FROM queue WHERE id = ?;';
            $params2 = [$id];
            $this->db->prepare($query2)->execute($params2);
            $this->db->commit();
            return (string)$row1['message'];
        } catch (Throwable $exception) {
            $this->db->rollBack();
        }
        return null;
    }
}
