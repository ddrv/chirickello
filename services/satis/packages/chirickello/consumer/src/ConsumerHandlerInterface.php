<?php

declare(strict_types=1);

namespace Chirickello\Package\Consumer;

interface ConsumerHandlerInterface
{
    public function handle(string $message, string $topic): void;
}
