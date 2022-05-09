<?php

declare(strict_types=1);

namespace Chirickello\Sender\Consumer;

use Chirickello\Package\Consumer\RabbitMQ\AbstractConsumer;
use Chirickello\Package\Event\BaseEvent;
use Chirickello\Package\Event\UserAdded\UserAdded;
use Chirickello\Package\EventPacker\EventPacker;
use Exception;
use Psr\EventDispatcher\EventDispatcherInterface;

class Consumer extends AbstractConsumer
{
    private EventDispatcherInterface $eventDispatcher;
    private array $allowedEvents = [
        UserAdded::class => true,
    ];

    public function __construct(EventPacker $packer, EventDispatcherInterface $eventDispatcher, string $dsn)
    {
        parent::__construct($packer, $dsn, 'sender');
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @inheritDoc
     */
    protected function handleEvent(BaseEvent $event): void
    {
        if (!array_key_exists(get_class($event), $this->allowedEvents)) {
            throw new Exception(sprintf('unexpected event: %s', $event->jsonSerialize()->event));
        }
        $this->eventDispatcher->dispatch($event);
    }
}
