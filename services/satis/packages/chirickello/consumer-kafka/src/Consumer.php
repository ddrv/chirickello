<?php

declare(strict_types=1);

namespace Chirickello\Package\Consumer\Kafka;

use Chirickello\Package\Consumer\ConsumerHandlerInterface;
use Chirickello\Package\Consumer\ConsumerInterface;
use longlang\phpkafka\Consumer\ConsumeMessage;
use longlang\phpkafka\Consumer\Consumer as KafkaConsumer;
use longlang\phpkafka\Consumer\ConsumerConfig;
use Throwable;

class Consumer implements ConsumerInterface
{
    private string $host;
    private int $port;
    private string $consumerName;
    private KafkaConsumer $consumer;

    public function __construct(string $dsn, string $consumerName)
    {
        $parts = parse_url('tcp://' . $dsn);
        $this->host = $parts['host'];
        $this->port = $parts['port'];
        $this->consumerName = $consumerName;
    }

    public function consume(string $topic, ConsumerHandlerInterface $handler): void
    {
        $config = new ConsumerConfig();
        $config->setBroker(sprintf('%s:%d', $this->host, $this->port));
        $config->setTopic($topic);
        $config->setGroupId(sprintf('%s.%s', $this->consumerName, $topic));
        $config->setClientId($this->consumerName);
        $config->setInterval(0.1);
        $config->setAutoCommit(false);

        $this->consumer = new KafkaConsumer($config, function (ConsumeMessage $message) use ($handler, $topic) {
            try {
                $handler->handle((string)$message->getValue(), $topic);
            } catch (Throwable $exception) {
            }
            $this->consumer->ack($message);
        });
        $this->consumer->start();
    }

    public function __destruct()
    {
        $this->consumer->close();
    }
}
