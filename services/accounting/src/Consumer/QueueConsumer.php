<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Consumer;

use Chirickello\Accounting\Queue\Queue;
use Chirickello\Package\Consumer\ConsumerHandlerInterface;
use Chirickello\Package\Consumer\ConsumerInterface;
use Throwable;

class QueueConsumer implements ConsumerInterface
{
    private Queue $queue;
    private int $interval;

    public function __construct(Queue $queue, int $interval = 5000)
    {
        $this->queue = $queue;
        $this->interval = $interval;
    }

    public function consume(string $topic, ConsumerHandlerInterface $handler): void
    {
        while (true) {
            $message = $this->queue->pull($topic);
            if (is_string($message)) {
                try {
                    $handler->handle($message, $topic);
                } catch (Throwable $exception) {
                }
            }
            usleep($this->interval);
        }
    }
}
