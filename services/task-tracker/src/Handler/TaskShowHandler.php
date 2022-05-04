<?php

declare(strict_types=1);

namespace Chirickello\TaskTracker\Handler;

use Chirickello\TaskTracker\Exception\TaskNotFoundException;
use Chirickello\TaskTracker\Repo\TaskRepo\TaskRepo;
use Chirickello\TaskTracker\Transformer\TaskTransformer;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TaskShowHandler implements RequestHandlerInterface
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
        $id = $request->getAttribute('id');
        try {
            $task = $this->taskRepo->getById($id);
        } catch (TaskNotFoundException $e) {
            $response = $this->responseFactory
                ->createResponse(404)
                ->withHeader('content-type', ['application/json'])
            ;
            $response->getBody()->write(json_encode([
                'message' => 'Task not found',
            ]));
            return $response;
        }

        $data = $this->transformer->transform([$task])[0];
        $response = $this->responseFactory->createResponse()->withHeader('content-type', ['application/json']);
        $response->getBody()->write(json_encode($data));
        return $response;
    }
}
