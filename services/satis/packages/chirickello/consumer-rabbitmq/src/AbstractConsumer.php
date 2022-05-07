<?php

declare(strict_types=1);

namespace Chirickello\Package\Consumer\RabbitMQ;

use Chirickello\Package\Consumer\ConsumerInterface;
use Closure;
use ErrorException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

abstract class AbstractConsumer implements ConsumerInterface
{
    private ?AMQPChannel $channel = null;
    private ?AMQPStreamConnection $connection = null;
    private string $host;
    private int $port;
    private string $user;
    private string $password;
    private string $consumerTag;

    public function __construct(string $dsn, string $consumerTag)
    {
        $parts = parse_url('tcp://' . $dsn);
        $this->host = $parts['host'];
        $this->port = $parts['port'];
        $this->user = $parts['user'] ?? 'guest';
        $this->password = $parts['pass'] ?? 'guest';
        $this->consumerTag = $consumerTag;
    }

    private function getChannel(): AMQPChannel
    {
        if (is_null($this->channel)) {
            $this->channel = $this->getConnection()->channel();
        }
        return $this->channel;
    }

    private function getConnection(): AMQPStreamConnection
    {
        if (is_null($this->connection)) {
            $this->connection = new AMQPStreamConnection($this->host, $this->port, $this->user, $this->password);
        }
        return $this->connection;
    }

    public function consume(string $topic): void
    {
        $channel = $this->getChannel();
        $channel->exchange_declare($topic, AMQPExchangeType::FANOUT, false, true, false);

        [$queue, ,] = $channel->queue_declare('', false, false, true, false);

        $channel->queue_bind($queue, $topic);
        $channel->basic_consume(
            $queue,
            $this->consumerTag,
            false,
            false,
            false,
            false,
            Closure::fromCallable([$this, 'processMessage'])
        );

        $attempt = 1;
        do {
            try {
                $channel->consume();
                $ok = true;
            } catch (ErrorException $e) {
                $ok = false;
                $attempt++;
                sleep(5);
            }
        } while (!$ok && $attempt < 10);
    }

    /**
     * @param AMQPMessage $message
     * @return void
     */
    private function processMessage(AMQPMessage $message): void
    {
        try {
            $this->handleMessage($message->body);
        } catch (Throwable $exception) {
            $this->handleException($exception);
        }
        $message->ack();

        if ($message->body === 'quit') {
            $message->getChannel()->basic_cancel($message->getConsumerTag());
        }
    }

    /**
     * @param string $message
     * @return void
     * @throws Throwable
     */
    abstract protected function handleMessage(string $message): void;

    protected function handleException(Throwable $exception): void
    {
    }

    public function __destruct()
    {
        if (!is_null($this->channel)) {
            try {
                $this->channel->close();
            } catch (Throwable $exception) {
            }
        }
        if (!is_null($this->connection)) {
            try {
                $this->connection->close();
            } catch (Throwable $e) {
            }
        }
    }
}
