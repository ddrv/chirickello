<?php

declare(strict_types=1);

namespace Chirickello\TaskTracker\Handler;

use Chirickello\TaskTracker\Exception\TaskNotFoundException;
use Chirickello\TaskTracker\Repo\Paginator;
use Chirickello\TaskTracker\Repo\TaskRepo\TaskFilter;
use Chirickello\TaskTracker\Repo\TaskRepo\TaskRepo;
use Chirickello\TaskTracker\Transformer\TaskTransformer;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TasksListHandler implements RequestHandlerInterface
{
    private ResponseFactoryInterface $responseFactory;
    private TaskRepo $taskRepo;
    private TaskTransformer $transformer;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        TaskRepo $taskRepo,
        TaskTransformer $transformer
    ) {
        $this->responseFactory = $responseFactory;
        $this->taskRepo = $taskRepo;
        $this->transformer = $transformer;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        $pageNum = (int)($query['pageNum'] ?? 1);
        $perPage = (int)($query['perPage'] ?? 20);
        if ($perPage < 1) {
            $perPage = 1;
        }
        if ($pageNum < 1) {
            $pageNum = 1;
        }
        $paginator = new Paginator($pageNum, $perPage);
        $filter = new TaskFilter();
        $total = $this->taskRepo->countFiltered($filter);
        $tasks = $this->taskRepo->getFiltered($filter, $paginator);
        $totalPages = (int)ceil($total / $perPage);

        $data = [
            'pages' => $totalPages,
            'tasks' => $this->transformer->transform($tasks),
        ];
        $response = $this->responseFactory->createResponse()->withHeader('content-type', ['application/json']);
        $response->getBody()->write(json_encode($data));
        return $response;
    }
}
