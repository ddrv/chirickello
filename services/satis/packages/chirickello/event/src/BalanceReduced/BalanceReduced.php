<?php

declare(strict_types=1);

namespace Chirickello\Package\Event\BalanceReduced;

use Chirickello\Package\Event\BaseEvent;

class BalanceReduced extends BaseEvent
{
    public function jsonSerialize(): object
    {
        return (object)[
            'event' => 'balance.reduced',
            'data' => (object)[],
        ];
    }
}
