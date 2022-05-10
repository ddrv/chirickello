<?php

declare(strict_types=1);

namespace Chirickello\Package\ConsumerEventHandler;

use Chirickello\Package\Consumer\ConsumerHandlerInterface;
use Chirickello\Package\EventPacker\EventPacker;
use Psr\EventDispatcher\EventDispatcherInterface;

class ConsumerEventHandler implements ConsumerHandlerInterface
{
    private EventPacker $packer;
    private EventDispatcherInterface $eventDispatcher;
    private array $map;

    public function __construct(
        EventPacker $packer,
        EventDispatcherInterface $eventDispatcher,
        array $handledEvents = ['*']
    ) {
        $this->packer = $packer;
        $this->eventDispatcher = $eventDispatcher;
        foreach ($handledEvents as $eventClassName) {
            $this->map[$eventClassName] = true;
        }
    }

    public function handle(string $message): void
    {
        $event = $this->packer->unpack($message);
        $eventClassName = get_class($event);
        if (array_key_exists('*', $this->map) || array_key_exists($eventClassName, $this->map)) {
            $this->eventDispatcher->dispatch($event);
        }
    }
}
