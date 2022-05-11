<?php

declare(strict_types=1);

namespace Chirickello\Package\Producer\Kafka;

use Chirickello\Package\Producer\ProducerInterface;
use longlang\phpkafka\Producer\Producer as KafkaProducer;
use longlang\phpkafka\Producer\ProducerConfig;

class Producer implements ProducerInterface
{
    private KafkaProducer $producer;
    private string $name;

    public function __construct(string $dsn, string $name)
    {
        $parts = parse_url('tcp://' . $dsn);
        $host = $parts['host'];
        $port = $parts['port'];
        $config = new ProducerConfig();
        $config->setBootstrapServer(sprintf('%s:%d', $host, $port));
        $config->setUpdateBrokers(true);
        $config->setAcks(-1);
        $this->producer = new KafkaProducer($config);
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function produce(string $message, string $topic): void
    {
        $this->producer->send($topic, $message, $this->generateKey());
    }

    private function generateKey(): string
    {
        // https://www.php.net/manual/en/function.uniqid.php#94959
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,

            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }

    public function __destruct()
    {
        $this->producer->close();
    }
}
