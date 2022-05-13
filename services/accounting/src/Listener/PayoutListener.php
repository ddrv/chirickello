<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Listener;

use Chirickello\Accounting\Entity\Transaction;
use Chirickello\Accounting\Event\BalanceCollected;
use Chirickello\Accounting\Exception\PayoutException;
use Chirickello\Accounting\Service\BankSdk\BankSdk;
use Chirickello\Accounting\Service\UserBalanceService\UserBalanceService;
use Chirickello\Accounting\Service\Workday\Workday;
use Chirickello\Package\Event\SalaryPaid\SalaryPaid;
use Chirickello\Package\Timer\TimerInterface;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class PayoutListener
{
    private UserBalanceService $userBalanceService;
    private Workday $workday;
    private TimerInterface $timer;
    private BankSdk $bankSdk;
    private EventDispatcherInterface $producingEventDispatcher;

    public function __construct(
        UserBalanceService       $userBalanceService,
        Workday                  $workday,
        TimerInterface           $timer,
        BankSdk                  $bankSdk,
        EventDispatcherInterface $producingEventDispatcher
    ) {
        $this->userBalanceService = $userBalanceService;
        $this->workday = $workday;
        $this->timer = $timer;
        $this->bankSdk = $bankSdk;
        $this->producingEventDispatcher = $producingEventDispatcher;
    }

    /**
     * @param object $event
     * @return void
     * @throws PayoutException
     */
    public function __invoke(object $event)
    {
        if (!$event instanceof BalanceCollected) {
            return;
        }

        $workday = $this->workday->workday($this->timer->now());
        $userId = $event->getUserId();
        $userBalance = $this->userBalanceService->getUserBalance($userId);
        if ($userBalance->getAmount() <= 0) {
            return;
        }

        $today = $this->convertDateTime($workday->end);
        $amount = $userBalance->getAmount();

        $this->bankSdk->payout($userId, $amount);

        $comment = 'salary for ' . $today->format('Y-m-d');
        $transaction = new Transaction(
            $userId,
            0,
            $amount,
            $comment,
            Transaction::PAYOUT,
            $this->timer->now()
        );
        $this->userBalanceService->addTransaction($transaction);

        $salaryPaidEvent = new SalaryPaid(
            $userId,
            $amount,
            $this->timer->now()
        );
        $this->producingEventDispatcher->dispatch($salaryPaidEvent);
    }

    private function convertDateTime(DateTimeInterface $dateTime): DateTimeImmutable
    {
        if ($dateTime instanceof DateTimeImmutable) {
            return $dateTime;
        }
        return DateTimeImmutable::createFromFormat('U', $dateTime->format('U'), $dateTime->getTimezone());
    }
}
