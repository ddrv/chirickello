<?php

declare(strict_types=1);

namespace Chirickello\Package\Event\BalanceCalculated;

use Chirickello\Package\Event\BaseEvent;

class BalanceCalculated extends BaseEvent
{
    public function jsonSerialize(): object
    {
        return (object)[
            'event' => 'balance.calculated',
            'data' => (object)[],
        ];
    }
}

