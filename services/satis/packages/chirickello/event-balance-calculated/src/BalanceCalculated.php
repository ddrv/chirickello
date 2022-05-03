<?php

declare(strict_types=1);

namespace Chirickello\Package\Event\BalanceCalculated;

use Chirickello\Package\Event\BaseEvent\BaseEvent;

class BalanceCalculated extends BaseEvent
{
    public function jsonSerialize(): array
    {
        return [
            'event' => 'balance.calculated',
            'data' => [],
        ];
    }
}

