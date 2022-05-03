<?php

declare(strict_types=1);

namespace Chirickello\Sender\Consumer;

use Chirickello\Package\Consumer\RabbitMQ\AbstractConsumer;
use Chirickello\Package\Event\UserAdded\UserAdded;
use Exception;
use Psr\EventDispatcher\EventDispatcherInterface;

class Consumer extends AbstractConsumer
{
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher, string $dsn)
    {
        parent::__construct($dsn, 'sender');
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @inheritDoc
     */
    protected function handleMessage(string $message): void
    {
        $array = json_decode($message, true);
        $eventName = $array['event'] ?? null;
        if (!is_string($eventName)) {
            throw new Exception(sprintf('message is not event: %s', $message));
        }
        switch ($eventName) {
            case 'user.added':
                $userId = $array['data']['userId'] ?? null;
                $login = $array['data']['login'] ?? null;
                $email = $array['data']['email'] ?? null;
                if (!is_string($userId) || !is_string($login) || !is_string($email)) {
                    throw new Exception(sprintf('incorrect message %s', $message));
                }
                $event = new UserAdded($userId, $login, $email);
                break;
            default:
                throw new Exception(sprintf('unexpected event: %s', $message));
        }
        $this->eventDispatcher->dispatch($event);
    }
}
