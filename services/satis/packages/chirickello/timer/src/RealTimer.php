<?php

declare(strict_types=1);

namespace Chirickello\Package\Timer;

use DateTimeImmutable;

class RealTimer implements TimerInterface
{
    /**
     * @return DateTimeImmutable
     */
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
