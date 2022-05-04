<?php

declare(strict_types=1);

namespace Chirickello\TaskTracker\Repo;

class Paginator
{
    public int $pageNum;
    public int $perPage;

    public function __construct(int $pageNum = 1, int $perPage = 20)
    {
        $this->pageNum = $pageNum;
        $this->perPage = $perPage;
    }
}
