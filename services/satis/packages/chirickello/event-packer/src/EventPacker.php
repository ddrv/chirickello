<?php

declare(strict_types=1);

namespace Chirickello\Package\EventPacker;

use Chirickello\Package\Event\BaseEvent;
use Chirickello\Package\Event\SalaryPaid\SalaryPaid;
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

    public function __construct(EventSchemaRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param BaseEvent $event
     * @return string
     * @throws RegistryException
     */
    public function pack(BaseEvent $event): string
    {
        $data = $event->jsonSerialize();
        $this->registry->check($data);
        return json_encode($data);
    }

    /**
     * @param string $message
     * @return BaseEvent
     * @throws RegistryException
     */
    public function unpack(string $message): BaseEvent
    {
        $event = json_decode($message);
        if (!is_object($event)) {
            throw new InvalidArgumentException('data is not json object');
        }
        $this->registry->check($event);
        switch ($event->event) {
            case 'user.added':
                return $this->createUserAddedEvent($event);
            case 'user.roles-assigned':
                return $this->createUserRolesAssignedEvent($event);
            case 'task.assigned':
                return $this->createTaskAssignedEvent($event);
            case 'task.completed':
                return $this->createTaskCompletedEvent($event);
            case 'salary.paid':
                return $this->createSalaryPaidEvent($event);
            default:
                throw new InvalidArgumentException('unknown event');
        }
    }

    private function createUserAddedEvent(object $event): UserAdded
    {
        /** @var object $data */
        $data = $event->data;
        return new UserAdded($data->userId, $data->login, $data->email);
    }

    private function createUserRolesAssignedEvent(object $event): UserRolesAssigned
    {
        /** @var object $data */
        $data = $event->data;
        return new UserRolesAssigned($data->userId, $data->roles);
    }

    private function createTaskAssignedEvent(object $event): TaskAssigned
    {
        /** @var object $data */
        $data = $event->data;
        return new TaskAssigned(
            $data->taskId,
            $data->assignedUserId,
            DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s', $data->time)
        );
    }

    private function createTaskCompletedEvent(object $event): TaskCompleted
    {
        /** @var object $data */
        $data = $event->data;
        return new TaskCompleted(
            $data->taskId,
            $data->assignedUserId,
            $data->taskDescription,
            DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s', $data->time)
        );
    }

    private function createSalaryPaidEvent(object $event): SalaryPaid
    {
        /** @var object $data */
        $data = $event->data;
        return new SalaryPaid(
            $data->userId,
            $data->amount,
            DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s', $data->time)
        );
    }
}
