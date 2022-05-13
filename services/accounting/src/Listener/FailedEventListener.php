<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Listener;

use Chirickello\Accounting\Event\EventFailed;
use Chirickello\Accounting\Queue\Queue;
use Chirickello\Accounting\Service\RetryStrategy\RetryStrategy;
use Chirickello\Package\EventPacker\EventPacker;

class FailedEventListener
{
    private Queue $queue;
    private RetryStrategy $strategy;
    private EventPacker $packer;
    private string $topic;

    public function __construct(
        Queue $queue,
        RetryStrategy $strategy,
        EventPacker $packer,
        string $topic
    ) {
        $this->queue = $queue;
        $this->strategy = $strategy;
        $this->packer = $packer;
        $this->topic = $topic;
    }

    public function __invoke(object $event)
    {
        if (!$event instanceof EventFailed) {
            return;
        }

        $nextTime = $this->strategy->getNextTime($event->getAttempt());
        $this->queue->push($this->topic, $this->packer->pack($event), $nextTime);
    }
}