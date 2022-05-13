<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Event;

use JsonSerializable;

class EventFailed implements JsonSerializable
{
    private string $payload;
    private string $reason;
    private int $attempt;

    public function __construct(string $payload, string $reason, int $attempt = 1)
    {
        $this->payload = $payload;
        $this->reason = $reason;
        $this->attempt = $attempt;
    }

    public function getPayload(): string
    {
        return $this->payload;
    }

    public function getAttempt(): int
    {
        return $this->attempt;
    }

    /**
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    public function fail(string $reason): void
    {
        if ($reason !== $this->reason) {
            $this->attempt = 1;
            return;
        }
        $this->attempt++;
    }

    public function jsonSerialize(): object
    {
        return (object)[
            'event' => 'event.failed',
            'data' => (object)[
                'payload' => $this->getPayload(),
                'reason' => $this->getReason(),
                'attempt' => $this->getAttempt(),
            ],
        ];
    }
}