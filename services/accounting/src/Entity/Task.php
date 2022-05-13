<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Entity;

class Task
{
    private string $id;
    private string $title;
    private int $tax;
    private int $cost;

    public function __construct(string $id, string $title, int $tax, int $cost)
    {
        $this->id = $id;
        $this->title = $title;
        $this->tax = $tax;
        $this->cost = $cost;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getTax(): int
    {
        return $this->tax;
    }

    public function getCost(): int
    {
        return $this->cost;
    }
}
