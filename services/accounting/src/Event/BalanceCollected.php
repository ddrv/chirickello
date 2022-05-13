<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Event;

use JsonSerializable;

class BalanceCollected implements JsonSerializable
{
    private string $userId;

    public function __construct(string $userId)
    {
        $this->userId = $userId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function jsonSerialize(): object
    {
        return (object)[
            'event' => 'balance.collected',
            'data' => (object)[
                'userId' => $this->getUserId(),
            ],
        ];
    }
}