<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Listener;

use Chirickello\Accounting\Event\BalanceCollected;
use Chirickello\Accounting\Event\WorkdayOver;
use Chirickello\Accounting\Service\UserBalanceService\UserBalanceService;
use Psr\EventDispatcher\EventDispatcherInterface;

class WorkdayClose
{
    private UserBalanceService $userBalanceService;
    private EventDispatcherInterface $producingEventDispatcher;

    public function __construct(
        UserBalanceService $userBalanceService,
        EventDispatcherInterface $producingEventDispatcher
    ) {
        $this->userBalanceService = $userBalanceService;
        $this->producingEventDispatcher = $producingEventDispatcher;
    }

    public function __invoke(object $event)
    {
        if (!$event instanceof WorkdayOver) {
            return;
        }

        $userBalances = $this->userBalanceService->getPositiveBalances();
        var_dump($userBalances);
        foreach ($userBalances as $userBalance) {
            $balanceCollectedEvent = new BalanceCollected($userBalance->getUserId());
            $this->producingEventDispatcher->dispatch($balanceCollectedEvent);
        }
    }
}
