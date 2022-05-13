<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Service\EventDispatcher;

use Psr\EventDispatcher\EventDispatcherInterface;

class EventDispatcherManager
{
    private EventDispatcherInterface $producing;
    private EventDispatcherInterface $listening;
    private EventDispatcherInterface $retrying;

    public function __construct(
        EventDispatcherInterface $producing,
        EventDispatcherInterface $listening,
        EventDispatcherInterface $retrying
    ) {
        $this->producing = $producing;
        $this->retrying = $retrying;
        $this->listening = $listening;
    }
    
    public function producing(): EventDispatcherInterface
    {
        return $this->producing;
    }

    public function listening(): EventDispatcherInterface
    {
        return $this->listening;
    }

    public function retrying(): EventDispatcherInterface
    {
        return $this->retrying;
    }
}
