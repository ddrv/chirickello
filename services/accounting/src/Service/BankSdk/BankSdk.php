<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Service\BankSdk;

use Chirickello\Accounting\Exception\PayoutException;

interface BankSdk
{
    /**
     * @param string $userId
     * @param int $amount
     * @return void
     * @throws PayoutException
     */
    public function payout(string $userId, int $amount): void;
}
