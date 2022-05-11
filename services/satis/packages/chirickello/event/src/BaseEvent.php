<?php

declare(strict_types=1);

namespace Chirickello\Package\Event;

use DateTimeImmutable;
use DateTimeZone;
use JsonSerializable;

abstract class BaseEvent implements JsonSerializable
{
    private ?string $eventId = null;
    private ?string $eventProducer = null;
    private ?DateTimeImmutable $eventTime = null;

    /**
     * @param string $producer
     * @param DateTimeImmutable $time
     * @return $this
     */
    public function preProduce(string $producer, DateTimeImmutable $time): self
    {
        $that = clone $this;
        $that->eventId = $this->generateUuid();
        $that->eventProducer = $producer;
        $that->eventTime = $time;
        return $that;
    }

    /**
     * @param string $id
     * @param string $producer
     * @param DateTimeImmutable $time
     * @return $this
     */
    public function postConsume(string $id, string $producer, DateTimeImmutable $time): self
    {
        $that = clone $this;
        $that->eventId = $id;
        $that->eventProducer = $producer;
        $that->eventTime = $time;
        return $that;
    }

    final public function getEventId(): ?string
    {
        return $this->eventId;
    }

    final public function getEventProducer(): ?string
    {
        return $this->eventProducer;
    }

    final public function getEventTime(): ?DateTimeImmutable
    {
        return $this->eventTime;
    }

    public function getEventVersion(): int
    {
        return 1;
    }

    final public function jsonSerialize(): object
    {
        $data = [];
        $id = $this->getEventId();
        if (!is_null($id)) {
            $data['id'] = $id;
        }
        $data['event'] = $this->getEventName();
        $data['version'] = $this->getEventVersion();
        $time = $this->getEventTime();
        $producer = $this->getEventProducer();
        if (!is_null($time)) {
            $data['time'] = $time->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.vP');
        }
        if (!is_null($producer)) {
            $data['producer'] = $producer;
        }
        $data['data'] = $this->jsonDataSerialize();
        return (object)$data;
    }

    abstract public function getEventName(): string;

    /**
     * @return object
     */
    abstract protected function jsonDataSerialize(): object;

    private function generateUuid(): string
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
}
