<?php

declare(strict_types=1);

namespace Chirickello\Package\Timer;

use DateTimeImmutable;

interface TimerInterface
{
    /**
     * @return DateTimeImmutable
     */
    public function now(): DateTimeImmutable;
}
