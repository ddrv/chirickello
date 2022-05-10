<?php

declare(strict_types=1);

namespace Chirickello\Package\Consumer;

interface ConsumerInterface
{
    public function consume(string $topic, ConsumerHandlerInterface $handler): void;
}
