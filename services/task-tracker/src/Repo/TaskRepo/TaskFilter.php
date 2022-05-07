<?php

declare(strict_types=1);

namespace Chirickello\TaskTracker\Repo\TaskRepo;

class TaskFilter
{
    public ?bool $isCompleted = null;
    public ?bool $reverse = true;
}
