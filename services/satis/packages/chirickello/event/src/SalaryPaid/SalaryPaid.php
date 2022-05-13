<?php

declare(strict_types=1);

namespace Chirickello\Package\Event\SalaryPaid;

use Chirickello\Package\Event\BaseEvent;
use DateTimeImmutable;
use DateTimeZone;

class SalaryPaid extends BaseEvent
{
    private string $userId;
    private int $amount;
    private DateTimeImmutable $paymentTime;

    public function __construct(
        string $userId,
        int $amount,
        DateTimeImmutable $paymentAssignTime
    ) {
        $this->userId = $userId;
        $this->amount = $amount;
        $this->paymentTime = $paymentAssignTime;
    }

    public function getEventName(): string
    {
        return 'salary.paid';
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getPaymentTime(): DateTimeImmutable
    {
        return $this->paymentTime;
    }

    public function jsonDataSerialize(): object
    {
        return (object)[
            'userId' => $this->userId,
            'amount' => $this->amount,
            'paymentTime' => $this->paymentTime->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.vP'),
        ];
    }
}
