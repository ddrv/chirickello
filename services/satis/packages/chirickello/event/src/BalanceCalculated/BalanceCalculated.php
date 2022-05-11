<?php

declare(strict_types=1);

namespace Chirickello\Package\Event\BalanceCalculated;

use Chirickello\Package\Event\BaseEvent;

class BalanceCalculated extends BaseEvent
{
    public function getEventName(): string
    {
        return 'balance.calculated';
    }

    public function jsonDataSerialize(): object
    {
        return (object)[
        ];
    }
}

