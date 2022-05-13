<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Service\EventDispatcher;

use Chirickello\Accounting\Event\EventFailed;
use Chirickello\Package\EventPacker\EventPacker;
use Psr\EventDispatcher\EventDispatcherInterface;
use Throwable;

class RetryEventDispatcher implements EventDispatcherInterface
{
    private EventDispatcherInterface $eventDispatcher;
    private EventPacker $packer;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        EventPacker $packer
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->packer = $packer;
    }

    public function dispatch(object $event)
    {
        try {
            $this->eventDispatcher->dispatch($event);
        } catch (Throwable $exception) {
            $this->eventDispatcher->dispatch(
                $this->createFailedEvent($event, $exception->getMessage())
            );
        }
    }

    /**
     * @param object $event
     * @param string $reason
     * @return EventFailed
     */
    private function createFailedEvent(object $event, string $reason): EventFailed
    {
        if ($event instanceof EventFailed) {
            $event->fail($reason);
            return $event;
        }
        return new EventFailed($this->packer->pack($event), $reason);
    }
}
