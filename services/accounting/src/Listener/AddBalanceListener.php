<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Listener;

use Chirickello\Accounting\Entity\Transaction;
use Chirickello\Accounting\Repo\TaskRepo\TaskRepo;
use Chirickello\Accounting\Repo\UserRepo\UserRepo;
use Chirickello\Accounting\Service\UserBalanceService\UserBalanceService;
use Chirickello\Package\Event\TaskCompleted\TaskCompleted;
use Chirickello\Package\Timer\TimerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class AddBalanceListener
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
        if (!$event instanceof TaskCompleted) {
            return;
        }

        $user = $this->userRepo->getById($event->getEmployeeUserId());
        $task = $this->taskRepo->getById($event->getTaskId());

        $comment = 'add for task: ' . $task->getTitle();

        $transaction = new Transaction(
            $user->getId(),
            $task->getCost(),
            0,
            $comment,
            Transaction::FOR_TASK,
            $this->timer->now()
        );
        $this->userBalanceService->addTransaction($transaction);
    }
}
