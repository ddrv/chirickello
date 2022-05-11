<?php

declare(strict_types=1);

namespace Chirickello\Package\Event\BalanceIncreased;

use Chirickello\Package\Event\BaseEvent;

class BalanceIncreased extends BaseEvent
{
    public function getEventName(): string
    {
        return 'balance.increased';
    }

    public function jsonDataSerialize(): object
    {
        return (object)[
        ];
    }
}

{
}
