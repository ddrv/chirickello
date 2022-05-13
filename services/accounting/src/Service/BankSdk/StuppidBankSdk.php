<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Service\BankSdk;

use Chirickello\Accounting\Exception\PayoutException;

class StuppidBankSdk implements BankSdk
{
    private int $max;
    private int $fail;

    public function __construct(float $failPercent)
    {
        $string = (string)$failPercent;
        if (strpos($string, '.') === false) {
            $string .= '.';
        }
        $pow = strlen(explode('.', $string)[1]);
        $this->max = pow(10, $pow + 2);
        $this->fail = (int)($failPercent * pow(10, $pow));
    }

    public function payout(string $userId, int $amount): void
    {
        $rand = rand(0, $this->max);
        if ($rand > $this->fail) {
            return;
        }
        throw new PayoutException('an error has occurred on the bank\'s side. try later');
    }
}
