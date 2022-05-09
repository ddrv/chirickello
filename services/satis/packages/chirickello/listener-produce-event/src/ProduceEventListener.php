<?php

declare(strict_types=1);

namespace Chirickello\Package\Listener\ProduceEventListener;

use Chirickello\Package\Event\BaseEvent;
use Chirickello\Package\Producer\ProducerInterface;

class ProduceEventListener
{
    private ProducerInterface $producer;
    private array $map = [];

    public function __construct(ProducerInterface $producer, string $defaultTopic = 'default')
    {
        $this->producer = $producer;
        $this->map['*'] = $defaultTopic;
    }

    public function bindEventToTopic(string $eventClassName, string $topic): void
    {
        if (!class_exists($eventClassName)) {
            return;
        }
        $this->map[$eventClassName] = $topic;
    }

    public function __invoke(object $event): void
    {
        if (!$event instanceof BaseEvent) {
            return;
        }

        $topic = $this->map[get_class($event)] ?? $this->map['*'];
        $this->producer->produce($event, $topic);
    }
}
