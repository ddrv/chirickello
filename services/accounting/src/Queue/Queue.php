<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Queue;

use DateTimeImmutable;

interface Queue
{
    public function push(string $queue, string $message, DateTimeImmutable $deferredTo): void;

    public function pull(string $queue): ?string;
}
