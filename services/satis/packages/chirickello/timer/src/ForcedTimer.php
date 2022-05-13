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
        $now = time();
        if ($this->speed === 1 || $this->begin->getTimestamp() > $now) {
            return new DateTimeImmutable();
        }
        $start = $this->begin->getTimestamp();
        $seconds = $now - $start;
        $micro = (int)((microtime(true) - $now) * $this->speed);
        $timestamp = $start + ($seconds * $this->speed) + $micro;
        return DateTimeImmutable::createFromFormat('U', (string)$timestamp);
    }

    public function getBeginDate(): DateTimeImmutable
    {
        return $this->begin;
    }

    public function getSpeed(): int
    {
        return $this->speed;
    }
}
