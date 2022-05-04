<?php

declare(strict_types=1);

namespace Chirickello\Package\Timer;

use DateTimeImmutable;

class ForcedTimer implements TimerInterface
{
    private DateTimeImmutable $begin;
    private int $speed;

    public function __construct(string $begin, int $speed)
    {
        $this->begin = DateTimeImmutable::createFromFormat('Y-m-d', $begin)->setTime(0, 0);
        $this->speed = $speed === 0 ? 1 : $speed;
    }

    /**
     * @return DateTimeImmutable
     */
    public function now(): DateTimeImmutable
    {
        if ($this->speed === 1) {
            return new DateTimeImmutable();
        }
        $start = $this->begin->getTimestamp();
        $seconds = time() - $start;
        $timestamp = $start + ($seconds * $this->speed);
        return DateTimeImmutable::createFromFormat('U', (string)$timestamp);
    }
}
