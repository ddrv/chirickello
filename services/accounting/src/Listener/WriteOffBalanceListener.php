<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Listener;

use Chirickello\Accounting\Entity\Transaction;
use Chirickello\Accounting\Repo\TaskRepo\TaskRepo;
use Chirickello\Accounting\Repo\UserRepo\UserRepo;
use Chirickello\Accounting\Service\UserBalanceService\UserBalanceService;
use Chirickello\Package\Event\TaskAssigned\TaskAssigned;
use Chirickello\Package\Timer\TimerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class WriteOffBalanceListener
{
    private UserRepo $userRepo;
    private TaskRepo $taskRepo;
    private UserBalanceService $userBalanceService;
    private TimerInterface $timer;

    public function __construct(
        UserRepo $userRepo,
        TaskRepo $taskRepo,
        UserBalanceService $userBalanceService,
        TimerInterface $timer
    ) {
        $this->userRepo = $userRepo;
        $this->taskRepo = $taskRepo;
        $this->userBalanceService = $userBalanceService;
        $this->timer = $timer;
    }

    public function __invoke(object $event, string $eventName, EventDispatcherInterface $eventDispatcher)
    {
        if (!$event instanceof TaskAssigned) {
            return;
        }

        $user = $this->userRepo->getById($event->getAssignedUserId());
        $task = $this->taskRepo->getById($event->getTaskId());

        $comment = 'write off for task: ' . $task->getTitle();

        $transaction = new Transaction(
            $user->getId(),
            0,
            $task->getTax(),
            $comment,
            Transaction::FOR_TASK,
            $this->timer->now()
        );
        $this->userBalanceService->addTransaction($transaction);
    }
}
