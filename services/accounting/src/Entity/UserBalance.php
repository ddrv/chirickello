<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Entity;

class UserBalance
{
    private string $userId;
    private int $amount;

    public function __construct(string $userId, int $amount)
    {
        $this->userId = $userId;
        $this->amount = $amount;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }
}
