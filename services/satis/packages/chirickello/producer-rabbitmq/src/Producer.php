<?php

declare(strict_types=1);

namespace Chirickello\Package\Producer\RabbitMQ;

use Chirickello\Package\Event\BaseEvent\BaseEvent;
use Chirickello\Package\Producer\ProducerInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

class Producer implements ProducerInterface
{
    private ?AMQPChannel $channel = null;
    private ?AMQPStreamConnection $connection = null;
    private string $host;
    private int $port;
    private string $user;
    private string $password;

    public function __construct(string $dsn)
    {
        $parts = parse_url('tcp://' . $dsn);
        $this->host = $parts['host'];
        $this->port = $parts['port'];
        $this->user = $parts['user'] ?? 'guest';
        $this->password = $parts['pass'] ?? 'guest';
    }

    public function produce(BaseEvent $event, string $topic): void
    {
        $channel = $this->getChannel();
        $channel->exchange_declare($topic, AMQPExchangeType::FANOUT, false, true, false);

        $message = new AMQPMessage(json_encode($event), [
            'content_type' => 'application/json',
        ]);
        $channel->basic_publish($message, $topic);
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
