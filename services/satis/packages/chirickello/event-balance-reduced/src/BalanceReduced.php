<?php

declare(strict_types=1);

namespace Chirickello\Package\Event\BalanceReduced;

use Chirickello\Package\Event\BaseEvent\BaseEvent;

class BalanceReduced extends BaseEvent
{
    public function jsonSerialize(): array
    {
        return [
            'event' => 'balance.reduced',
            'data' => [],
        ];
    }
}
