<?php

declare(strict_types=1);

namespace Chirickello\Package\EventPacker;

use Chirickello\Package\Event\BaseEvent;
use Chirickello\Package\Event\SalaryPaid\SalaryPaid;
use Chirickello\Package\Event\TaskAdded\TaskAdded;
use Chirickello\Package\Event\TaskAssigned\TaskAssigned;
use Chirickello\Package\Event\TaskCompleted\TaskCompleted;
use Chirickello\Package\Event\UserAdded\UserAdded;
use Chirickello\Package\Event\UserRolesAssigned\UserRolesAssigned;
use Chirickello\Package\EventSchemaRegistry\EventSchemaRegistry;
use Chirickello\Package\EventSchemaRegistry\Exception\RegistryException;
use DateTimeImmutable;
use InvalidArgumentException;

class EventPacker
{
    private EventSchemaRegistry $registry;
    private array $transformers = [];

    final public function __construct(EventSchemaRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param object $event
     * @return string
     * @throws RegistryException
     */
    final public function pack(object $event): string
    {
        $data = $event->jsonSerialize();
        $this->registry->check($data);
        return json_encode($data);
    }

    /**
     * @param string $message
     * @return object
     * @throws RegistryException
     */
    final public function unpack(string $message): object
    {
        $event = json_decode($message);
        if (!is_object($event)) {
            throw new InvalidArgumentException('data is not json object');
        }
        return $this->transform($event);
    }

    final public function addTransformer(string $eventName, EventTransformerInterface $transformer): void
    {
        $this->transformers[$eventName] = $transformer;
    }

    /**
     * @param object $event
     * @return object
     * @throws RegistryException
     */
    private function transform(object $event): object
    {
        $this->registry->check($event);
        switch ($event->event) {
            case 'user.added':
                $unpacked = $this->createUserAddedEvent($event);
                break;
            case 'user.roles-assigned':
                $unpacked = $this->createUserRolesAssignedEvent($event);
                break;
            case 'task.added':
                $unpacked = $this->createTaskAddedEvent($event);
                break;
            case 'task.assigned':
                $unpacked = $this->createTaskAssignedEvent($event);
                break;
            case 'task.completed':
                $unpacked = $this->createTaskCompletedEvent($event);
                break;
            case 'salary.paid':
                $unpacked = $this->createSalaryPaidEvent($event);
                break;
            default:
                $unpacked = $this->transformUnknown($event);
        }
        return $unpacked;
    }

    private function transformUnknown(object $event): object
    {
        $transformer = $this->transformers[$event->event] ?? null;
        if (is_null($transformer)) {
            return $event;
        }
        $unpacked = $transformer->transform($event);
        if (!$unpacked instanceof BaseEvent) {
            return $unpacked;
        }
        if (
            !is_null($unpacked->getEventId())
            && !is_null($unpacked->getEventProducer())
            && !is_null($unpacked->getEventTime())
        ) {
            return $unpacked;
        }
        if (!property_exists($event, 'id') || !is_string($event->id)) {
            return $unpacked;
        }
        if (!property_exists($event, 'producer') || !is_string($event->producer)) {
            return $unpacked;
        }
        if (!property_exists($event, 'time') || !is_string($event->time)) {
            return $unpacked;
        }
        [$id, $producer, $time] = $this->getProducerData($event);
        return $unpacked->postConsume($id, $producer, $time);
    }

    private function createUserAddedEvent(object $event): UserAdded
    {
        /** @var object $data */
        $data = $event->data;
        $unpacked = new UserAdded(
            $data->userId,
            $data->login,
            $data->email
        );
        [$id, $producer, $time] = $this->getProducerData($event);
        return $unpacked->postConsume($id, $producer, $time);
    }

    private function createUserRolesAssignedEvent(object $event): UserRolesAssigned
    {
        /** @var object $data */
        $data = $event->data;
        $unpacked = new UserRolesAssigned(
            $data->userId,
            $data->roles
        );
        [$id, $producer, $time] = $this->getProducerData($event);
        return $unpacked->postConsume($id, $producer, $time);
    }

    private function createTaskAddedEvent(object $event): TaskAdded
    {
        /** @var object $data */
        $data = $event->data;
        $unpacked = new TaskAdded(
            $data->taskId,
            $data->taskTitle
        );
        [$id, $producer, $time] = $this->getProducerData($event);
        return $unpacked->postConsume($id, $producer, $time);
    }

    private function createTaskAssignedEvent(object $event): TaskAssigned
    {
        /** @var object $data */
        $data = $event->data;
        $unpacked = new TaskAssigned(
            $data->taskId,
            $data->assignedUserId,
            DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.vP', $data->assignTime)
        );
        [$id, $producer, $time] = $this->getProducerData($event);
        return $unpacked->postConsume($id, $producer, $time);
    }

    private function createTaskCompletedEvent(object $event): TaskCompleted
    {
        /** @var object $data */
        $data = $event->data;
        $unpacked = new TaskCompleted(
            $data->taskId,
            $data->employeeUserId,
            DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.vP', $data->completionTime)
        );
        [$id, $producer, $time] = $this->getProducerData($event);
        return $unpacked->postConsume($id, $producer, $time);
    }

    private function createSalaryPaidEvent(object $event): SalaryPaid
    {
        /** @var object $data */
        $data = $event->data;
        $unpacked = new SalaryPaid(
            $data->userId,
            $data->amount,
            DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.vP', $data->paymentTime)
        );
        [$id, $producer, $time] = $this->getProducerData($event);
        return $unpacked->postConsume($id, $producer, $time);
    }

    private function getProducerData(object $event): array
    {
        return [
            $event->id,
            $event->producer,
            DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.vP', $event->time),
        ];
    }
}
