<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Service\RetryStrategy;

use Chirickello\Package\Timer\TimerInterface;
use DateTimeImmutable;

class FibonacciStrategy implements RetryStrategy
{
    private TimerInterface $timer;

    public function __construct(TimerInterface $timer)
    {
        $this->timer = $timer;
    }

    public function getNextTime(int $attempt): DateTimeImmutable
    {
        $attempt = min($attempt, 25);
        $delay = $this->fib($attempt + 1);
        $delay = min($delay, 86400);
        return $this->timer->now()->modify(sprintf('+%d seconds', $delay));
    }

    private function fib(int $i): int
    {
        $fib = [
            0,
            1,
            1,
            2,
            3,
            5,
            8,
            13,
            21,
            34,
            55,
            89,
            144,
            233,
            377,
            610,
            987,
            1597,
            2584,
            4181,
            6765,
            10946,
            17711,
            28657,
            46368,
            75025,
            121393,
        ];
        if ($i <= 0 ) return 0;
        if (array_key_exists($i, $fib)) {
            return $fib[$i];
        }
        return $this->fib($i - 1) + $this->fib($i - 2);
    }
}
