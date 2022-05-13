<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Producer;

use Chirickello\Accounting\Queue\Queue;
use Chirickello\Package\Producer\ProducerInterface;
use Chirickello\Package\Timer\TimerInterface;

class QueueProducer implements ProducerInterface
{
    private Queue $queue;
    private TimerInterface $timer;
    private string $name;

    public function __construct(Queue $queue, TimerInterface $timer, string $name)
    {
        $this->queue = $queue;
        $this->timer = $timer;
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function produce(string $message, string $topic): void
    {
        $this->queue->push($topic, $message, $this->timer->now());
    }
}
