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

    public function handle(string $message): void
    {
        $id = uniqid('', true);
        $this->logger->info($id . ': consuming message ' . $message);
        try {
            $this->handler->handle($message);
        } catch (Throwable $exception) {
            $this->logger->info($id . ': consuming error ' . $exception->getMessage());
            throw $exception;
        }
    }
}
