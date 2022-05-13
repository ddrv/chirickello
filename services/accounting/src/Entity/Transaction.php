<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

class Transaction
{
    public const FOR_TASK = 'for_task';
    public const PAYOUT = 'payout';

    private ?string $id;
    private string $userId;
    private int $debit;
    private int $credit;
    private string $comment;
    private string $type;
    private DateTimeImmutable $time;

    public function __construct(
        string $userId,
        int $debit,
        int $credit,
        string $comment,
        string $type,
        DateTimeImmutable $time,
        ?string $id = null
    ) {
        if ($debit < 0) {
            throw new InvalidArgumentException('debit cannot be less than zero');
        }
        if ($credit < 0) {
            throw new InvalidArgumentException('credit cannot be less than zero');
        }
        if ($credit + $debit === 0) {
            throw new InvalidArgumentException('debit or credit must be greater than zero');
        }
        $this->userId = $userId;
        $this->debit = $debit;
        $this->credit = $credit;
        $this->comment = $comment;
        $this->time = $time;
        $this->type = $type;
        $this->id = $id;
    }

    public function isNew(): bool
    {
        return is_null($this->id);
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        if (is_null($this->id) || $id === $this->id) {
            $this->id = $id;
            return;
        }
        throw new RuntimeException('can not change id');
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getDebit(): int
    {
        return $this->debit;
    }

    public function getCredit(): int
    {
        return $this->credit;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function getTime(): DateTimeImmutable
    {
        return $this->time;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
