<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Listener;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class LazyLoadListener
{
    private ContainerInterface $container;
    private string $key;
    /**
     * @var callable|null
     */
    private $listener = null;

    public function __construct(ContainerInterface $container, string $key)
    {
        $this->container = $container;
        $this->key = $key;
    }

    public function __invoke(object $event, string $eventName, EventDispatcherInterface $eventDispatcher)
    {
        ($this->getListener())($event, $eventName, $eventDispatcher);
    }

    private function getListener(): callable
    {
        if (!is_callable($this->listener)) {
            $listener = $this->container->get($this->key);
            if (!is_callable($listener)) {
                return function () {};
            }
            $this->listener = $listener;
        }
        return $this->listener;
    }
}
