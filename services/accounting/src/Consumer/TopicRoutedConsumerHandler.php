<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Consumer;

use Chirickello\Package\Consumer\ConsumerHandlerInterface;
use Chirickello\Package\ConsumerEventHandler\ConsumerEventHandler;
use InvalidArgumentException;

class TopicRoutedConsumerHandler implements ConsumerHandlerInterface
{
    /**
     * @var ConsumerEventHandler[]
     */
    private array $router = [];

    public function __construct(ConsumerHandlerInterface $handler, string $topic, string ...$topics)
    {
        $topics[] = $topic;
        $this->addHandler($handler, ...$topics);
    }

    public function addHandler(ConsumerHandlerInterface $handler, string $topic, string ...$topics): void
    {
        array_unshift($topics, $topic);
        foreach ($topics as $topic) {
            $this->router[$topic] = $handler;
        }
    }

    public function handle(string $message, string $topic): void
    {
        $handler = $this->router[$topic] ?? null;
        if (!$handler instanceof ConsumerHandlerInterface) {
            throw new InvalidArgumentException(sprintf('consumer handler for %s topic not found', $topic));
        }
        $handler->handle($message, $topic);
    }
}
