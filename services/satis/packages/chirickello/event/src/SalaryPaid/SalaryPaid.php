<?php

declare(strict_types=1);

namespace Chirickello\Package\Event\SalaryPaid;

use Chirickello\Package\Event\BaseEvent;
use DateTimeImmutable;

class SalaryPaid extends BaseEvent
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

    public function jsonSerialize(): object
    {
        return (object)[
            'event' => 'salary.paid',
            'data' => (object)[
                'userId' => $this->userId,
                'amount' => $this->amount,
                'date' => $this->date->format('Y-m-d\TH:i:s')
            ],
        ];
    }
}
