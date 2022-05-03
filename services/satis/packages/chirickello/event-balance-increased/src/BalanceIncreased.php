<?php

declare(strict_types=1);

namespace Chirickello\Package\Event\BalanceIncreased;

use Chirickello\Package\Event\BaseEvent\BaseEvent;

class BalanceIncreased extends BaseEvent
{
    public function jsonSerialize(): array
    {
        return [
            'event' => 'balance.increased',
            'data' => [],
        ];
    }
}

{
}
