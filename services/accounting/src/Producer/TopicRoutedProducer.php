<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Producer;

use Chirickello\Package\Producer\ProducerInterface;
use InvalidArgumentException;

class TopicRoutedProducer implements ProducerInterface
{
    /**
     * @var ProducerInterface[]
     */
    private array $router = [];

    private string $name;

    public function __construct(ProducerInterface $producer, string $topic, string ...$topics)
    {
        $topics[] = $topic;
        $this->name = $producer->getName();
        $this->addProducer($producer, ...$topics);
    }

    public function addProducer(ProducerInterface $producer, string $topic, string ...$topics): void
    {
        array_unshift($topics, $topic);
        foreach ($topics as $topic) {
            $this->router[$topic] = $producer;
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function produce(string $message, string $topic): void
    {
        $producer = $this->router[$topic] ?? null;
        if (!$producer instanceof ProducerInterface) {
            throw new InvalidArgumentException(sprintf('producer for %s topic not found', $topic));
        }
        $producer->produce($message, $topic);
    }
}
