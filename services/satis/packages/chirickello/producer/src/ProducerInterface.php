<?php

declare(strict_types=1);

namespace Chirickello\Package\Producer;

use Chirickello\Package\Event\BaseEvent\BaseEvent;

interface ProducerInterface
{
    public function produce(BaseEvent $event, string $topic): void;
}
