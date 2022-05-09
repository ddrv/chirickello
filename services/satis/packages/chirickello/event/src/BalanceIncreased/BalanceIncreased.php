<?php

declare(strict_types=1);

namespace Chirickello\Package\Event\BalanceIncreased;

use Chirickello\Package\Event\BaseEvent;

class BalanceIncreased extends BaseEvent
{
    public function jsonSerialize(): object
    {
        return (object)[
            'event' => 'balance.increased',
            'data' => (object)[],
        ];
    }
}

{
}
