<?php

declare(strict_types=1);

namespace Chirickello\Package\ConsumerLoggedHandler;

use Chirickello\Package\Consumer\ConsumerHandlerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class ConsumerLoggedHandler implements ConsumerHandlerInterface
{
    private ConsumerHandlerInterface $handler;
    private LoggerInterface $logger;

    public function __construct(
        ConsumerHandlerInterface $handler,
        LoggerInterface $logger
    ) {
        $this->handler = $handler;
        $this->logger = $logger;
    }

    public function handle(string $message, string $topic): void
    {
        $id = uniqid('', true);
        $this->logger->info(sprintf('[%s] handling message consumed from %s topic: %s', $id, $topic, $message));
        try {
            $this->handler->handle($message, $topic);
        } catch (Throwable $exception) {
            $this->logger->info(sprintf('[%s] handling error: %s', $id, $exception->getMessage()));
            throw $exception;
        }
    }
}
