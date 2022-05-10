<?php

declare(strict_types=1);

namespace Chirickello\Package\Consumer\RabbitMQ;

use Chirickello\Package\Consumer\ConsumerHandlerInterface;
use Chirickello\Package\Consumer\ConsumerInterface;
use ErrorException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

class Consumer implements ConsumerInterface
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

    public function consume(string $topic, ConsumerHandlerInterface $handler): void
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
            function (AMQPMessage $message) use ($handler) {
                try {
                    $handler->handle($message->body);
                } catch (Throwable $exception) {
                }
                $message->ack();

                if ($message->body === 'quit') {
                    $message->getChannel()->basic_cancel($message->getConsumerTag());
                }
            }
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
