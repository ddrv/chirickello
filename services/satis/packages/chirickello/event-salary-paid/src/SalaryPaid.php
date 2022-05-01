<?php

declare(strict_types=1);

namespace Chirickello\Package\Event;

use DateTimeImmutable;

class SalaryPaid
{
    private string $userId;
    private float $amount;
    private DateTimeImmutable $date;

    public function __construct(string $userId, float $amount, DateTimeImmutable $date)
    {
        $this->userId = $userId;
        $this->amount = $amount;
        $this->date = $date;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }
}
