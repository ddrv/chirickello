<?php

declare(strict_types=1);

namespace Chirickello\Package\Event\BalanceReduced;

use Chirickello\Package\Event\BaseEvent;

class BalanceReduced extends BaseEvent
{
    public function getEventName(): string
    {
        return 'balance.reduced';
    }

    public function jsonDataSerialize(): object
    {
        return (object)[
        ];
    }
}
