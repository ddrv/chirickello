<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Consumer;

use Chirickello\Accounting\Event\EventFailed;
use Chirickello\Package\Consumer\ConsumerHandlerInterface;
use Chirickello\Package\EventPacker\EventPacker;
use Psr\EventDispatcher\EventDispatcherInterface;
use Throwable;

class FailedEventHandler implements ConsumerHandlerInterface
{
    private EventPacker $packer;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        EventPacker              $packer,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->packer = $packer;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function handle(string $message, string $topic): void
    {
        /** @var EventFailed $event */
        $event = $this->packer->unpack($message);

        try {
            $failedEvent = $this->packer->unpack($event->getPayload());
            $this->eventDispatcher->dispatch($failedEvent);
        } catch (Throwable $exception) {
            $event->fail($exception->getMessage());
            $this->eventDispatcher->dispatch($event);
        }
    }
}
