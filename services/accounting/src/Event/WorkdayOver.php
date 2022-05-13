<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Event;

use DateTimeImmutable;
use JsonSerializable;

class WorkdayOver implements JsonSerializable
{
    private DateTimeImmutable $time;

    public function __construct(DateTimeImmutable $time)
    {
        $this->time = $time;
    }

    public function getTime(): DateTimeImmutable
    {
        return $this->time;
    }

    public function jsonSerialize(): object
    {
        return (object)[
            'event' => 'workday.over',
            'version' => 1,
            'data' => (object)[
                'time' => $this->time->format('Y-m-d\TH:i:s.vP'),
            ],
        ];
    }
}
