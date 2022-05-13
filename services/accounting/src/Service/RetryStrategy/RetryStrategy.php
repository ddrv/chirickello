<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Service\RetryStrategy;

use DateTimeImmutable;

interface RetryStrategy
{
    public function getNextTime(int $attempt): DateTimeImmutable;
}
