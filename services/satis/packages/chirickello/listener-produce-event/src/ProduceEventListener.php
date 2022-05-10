<?php

declare(strict_types=1);

namespace Chirickello\Package\Listener\ProduceEventListener;

use Chirickello\Package\Event\BaseEvent;
use Chirickello\Package\EventPacker\EventPacker;
use Chirickello\Package\EventSchemaRegistry\Exception\RegistryException;
use Chirickello\Package\Producer\ProducerInterface;
use Psr\Log\LoggerInterface;

class ProduceEventListener
{
    private LoggerInterface $logger;
    private ProducerInterface $producer;
    private EventPacker $packer;
    private array $map = [];

    public function __construct(
        LoggerInterface $logger,
        EventPacker $packer,
        ProducerInterface $producer,
        string $defaultTopic = 'default'
    ) {
        $this->logger = $logger;
        $this->producer = $producer;
        $this->map['*'] = $defaultTopic;
        $this->packer = $packer;
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
        $id = uniqid('', true);
        try {
            $message = $this->packer->pack($event);
        } catch (RegistryException $exception) {
            $this->logger->error(sprintf('[%s] error pack message: [%s]', $id, $exception->getMessage()));
            throw $exception;
        }
        $this->logger->info(sprintf('[%s] producing message to %s topic: [%s]', $id, $topic, $message));
        $this->producer->produce($message, $topic);
    }
}
