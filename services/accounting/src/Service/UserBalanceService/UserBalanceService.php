<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Service\UserBalanceService;

use Chirickello\Accounting\Entity\Transaction;
use Chirickello\Accounting\Entity\UserBalance;
use Chirickello\Accounting\Exception\StorageException;
use DatePeriod;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Throwable;

class UserBalanceService
{
    private PDO $db;
    private DateTimeZone $utc;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->utc = new DateTimeZone('UTC');
    }

    public function getUserBalance(string $userId): UserBalance
    {
        $query = 'SELECT * FROM user_balance WHERE user_id = ?;';
        $st = $this->db->prepare($query);
        $st->execute([$userId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (empty($row)) {
            return $this->hydrateUserBalance([
                $userId,
                0,
            ]);
        }
        return $this->hydrateUserBalance($row);
    }

    /**
     * @return UserBalance[]
     */
    public function getPositiveBalances(): array
    {
        $query = 'SELECT * FROM user_balance WHERE amount > 0;';
        $st = $this->db->prepare($query);
        $st->execute();
        $result = [];
        while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $this->hydrateUserBalance($row);
        }
        return $result;
    }

    public function addTransaction(Transaction $transaction): void
    {
        $this->db->beginTransaction();
        try {
            $id = null;
            if (!$transaction->isNew()) {
                $query1 = 'SELECT id FROM transactions WHERE id = ?';
                $st1 = $this->db->prepare($query1);
                $st1->execute([$transaction->getId()]);
                $row = $st1->fetch(PDO::FETCH_ASSOC);
                if (!empty($row)) {
                    throw new StorageException('transaction already saved');
                }
                $id = $transaction->getId();
            }
            if (is_null($id)) {
                $id = $this->generateId();
            }
            $query2 = 'INSERT INTO transactions (id, user_id, debit, credit, time, comment, type) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?);'
            ;
            $st2 = $this->db->prepare($query2);
            $st2->execute([
                $id,
                $transaction->getUserId(),
                $transaction->getDebit(),
                $transaction->getCredit(),
                $transaction->getTime()->setTimezone($this->utc)->format('Y-m-d H:i:s'),
                $transaction->getComment(),
                $transaction->getType(),
            ]);

            $query3 = 'INSERT INTO user_balance (user_id, amount) VALUES (:user_id, :amount) '
                . 'ON CONFLICT (user_id) DO UPDATE SET amount = amount + :amount;'
            ;
            $st3 = $this->db->prepare($query3);
            $st3->execute([
                $transaction->getUserId(),
                ($transaction->getDebit() - $transaction->getCredit()),
            ]);

            $this->db->commit();
        } catch (StorageException $exception) {
            $this->db->rollBack();
            throw $exception;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw new StorageException('storage error', 0, $exception);
        }
    }

    public function calculateTopManagementProfit(DatePeriod $period): int
    {
        $query = 'SELECT (SUM(debit) - SUM(credit)) AS profit FROM transactions '
            . 'WHERE time >= ? AND time <= ? AND type != ?;'
        ;
        $st = $this->db->prepare($query);
        $from = DateTimeImmutable::createFromFormat('U', $period->start->format('U'))->setTimezone($this->utc);
        $to = DateTimeImmutable::createFromFormat('U', $period->end->format('U'))->setTimezone($this->utc);
        $st->execute([
            $from->format('Y-m-d H:i:s'),
            $to->format('Y-m-d H:i:s'),
            Transaction::PAYOUT,
        ]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (empty($row)) {
            return 0;
        }
        return -(int)$row['profit'];
    }

    private function generateId(): string
    {
        $st = $this->db->prepare('SELECT id FROM transactions WHERE id = ?;');
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

    private function hydrateUserBalance(array $row): UserBalance
    {
        return new UserBalance(
            (string)$row['user_id'],
            (int)$row['amount']
        );
    }
}
