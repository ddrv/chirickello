<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Consumer;

use Chirickello\Package\Consumer\ConsumerHandlerInterface;
use Chirickello\Package\Consumer\ConsumerInterface;
use InvalidArgumentException;

class TopicRoutedConsumer implements ConsumerInterface
{
    /**
     * @var ConsumerInterface[]
     */
    private array $router = [];

    public function __construct(ConsumerInterface $consumer, string $topic, string ...$topics)
    {
        $topics[] = $topic;
        $this->addConsumer($consumer, ...$topics);
    }

    public function addConsumer(ConsumerInterface $consumer, string $topic, string ...$topics): void
    {
        array_unshift($topics, $topic);
        foreach ($topics as $topic) {
            $this->router[$topic] = $consumer;
        }
    }

    public function consume(string $topic, ConsumerHandlerInterface $handler): void
    {
        $consumer = $this->router[$topic] ?? null;
        if (!$consumer instanceof ConsumerInterface) {
            throw new InvalidArgumentException(sprintf('consumer for %s topic not found', $topic));
        }
        $consumer->consume($topic, $handler);
    }
}
