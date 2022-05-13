<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Service\Workday;

use Chirickello\Package\Timer\TimerInterface;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

class Workday
{

    private TimerInterface $timer;
    private array $workdayOver;
    private DateTimeZone $timezone;

    public function __construct(TimerInterface $timer, string $workdayOver, string $timezone)
    {
        $this->timer = $timer;
        preg_match('/^(?<h>(([0-1][0-9])|(2[0-3])))(:(?<m>[0-5][0-9])(:(?<s>[0-5][0-9]))?)?$/ui', $workdayOver, $matches);
        if (empty($matches)) {
            throw new InvalidArgumentException('invalid workdayOver. use HH, HH:MM or HH:MM:SS format');
        }

        $this->workdayOver = [
            (int)$matches['h'],
            (int)($matches['m'] ?? 0),
            (int)($matches['s'] ?? 0)
        ];
        $this->timezone = new DateTimeZone($timezone);
    }

    public function workday(?DateTimeImmutable $time = null): DatePeriod
    {
        if (is_null($time)) {
            $time = $this->timer->now();
        }
        $time = $time->setTimezone($this->timezone);

        $end = $time->setTime(...$this->workdayOver);
        if ($end >= $time) {
            $start = $end->modify('-1 day')->modify('+1 second');
        } else {
            $start = $end->modify('+1 second');
            $end = $end->modify('+1 day');
        }
        return new DatePeriod($start, new DateInterval('P1D'), $end);
    }

    /**
     * @return int[]
     */
    public function getWorkdayOver(): array
    {
        return $this->workdayOver;
    }
}
