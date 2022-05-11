<?php

declare(strict_types=1);

namespace Chirickello\TaskTracker\Entity;

use DateTimeImmutable;
use RuntimeException;

class Task
{
    private ?string $id;
    private string $authorId;
    private string $assignedTo;
    private string $title;
    private bool $isCompleted = false;
    private DateTimeImmutable $createdAt;
    private array $original;

    public function __construct(
        string $authorId,
        string $assignedTo,
        string $title,
        DateTimeImmutable $createdAt,
        ?string $id = null
    ) {
        $this->authorId = $authorId;
        $this->assignedTo = $assignedTo;
        $this->title = $title;
        $this->createdAt = $createdAt;
        $this->id = $id;
        $this->flush();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function isNew(): bool
    {
        return $this->id === null;
    }

    public function setId(string $id): void
    {
        if (!is_null($this->id) && $this->id !== $id) {
            throw new RuntimeException('can not change id');
        }
        $this->id = $id;
    }

    public function getAuthorId(): string
    {
        return $this->authorId;
    }

    public function getAssignedTo(): string
    {
        return $this->assignedTo;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isCompleted(): bool
    {
        return $this->isCompleted;
    }

    public function assign(string $userId): void
    {
        $this->assignedTo = $userId;
    }

    public function complete(): void
    {
        $this->isCompleted = true;
    }

    public function isAssignedToChanged(): bool
    {
        return $this->assignedTo !== $this->original['assignedTo'];
    }

    public function isCompletedChanged(): bool
    {
        return $this->isCompleted !== $this->original['isCompleted'];
    }

    public function flush()
    {
        $this->original = [
            'assignedTo' => $this->assignedTo,
            'isCompleted' => $this->isCompleted,
        ];
    }
}
