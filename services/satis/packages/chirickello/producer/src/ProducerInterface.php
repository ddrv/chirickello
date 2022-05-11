<?php

declare(strict_types=1);

namespace Chirickello\Package\Producer;

interface ProducerInterface
{
    public function getName(): string;

    public function produce(string $message, string $topic): void;
}
