<?php

declare(strict_types=1);

namespace Chirickello\TaskTracker\Handler;

use Chirickello\Package\Timer\TimerInterface;
use Chirickello\TaskTracker\Exception\TaskNotFoundException;
use Chirickello\TaskTracker\Repo\TaskRepo\TaskRepo;
use Chirickello\TaskTracker\Transformer\TaskTransformer;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TaskUpdateHandler implements RequestHandlerInterface
{
    private ResponseFactoryInterface $responseFactory;
    private EventDispatcherInterface $eventDispatcher;
    private TimerInterface $timer;
    private TaskRepo $taskRepo;
    private TaskTransformer $transformer;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        EventDispatcherInterface $eventDispatcher,
        TimerInterface $timer,
        TaskRepo $taskRepo,
        TaskTransformer $transformer
    ) {
        $this->responseFactory = $responseFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->timer = $timer;
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
        $post = $request->getParsedBody();
        $user = $request->getAttribute('user');
        $userId = $user['id'];
        if ($userId !== $task->getAssignedTo()) {
            $response = $this->responseFactory
                ->createResponse(403)
                ->withHeader('content-type', ['application/json'])
            ;
            $response->getBody()->write(json_encode([
                'message' => 'It is not your task',
            ]));
            return $response;
        }
        if ($task->isCompleted()) {
            $response = $this->responseFactory
                ->createResponse(403)
                ->withHeader('content-type', ['application/json'])
            ;
            $response->getBody()->write(json_encode([
                'message' => 'Task already completed',
            ]));
            return $response;
        }
        $errors = [];
        if (!empty($errors)) {
            return $this->createErrorResponse($errors);
        }
        $task->complete();
        $this->taskRepo->save($task);
        // todo send event `task.completed`

        $view = $this->transformer->transform([$task])[0];

        $response = $this->responseFactory->createResponse()->withHeader('content-type', ['application/json']);
        $response->getBody()->write(json_encode($view));
        return $response;
    }

    private function createErrorResponse($errors): ResponseInterface
    {
        $data = [];
        foreach ($errors as $field => $messages) {
            foreach ($messages as $message) {
                $data[$field][] = [
                    'field' => $field,
                    'message' => $message,
                ];
            }
        }
        $response = $this->responseFactory
            ->createResponse(422)
            ->withHeader('content-type', ['application/json'])
        ;
        $response->getBody()->write(json_encode(array_values($data)));
        return $response;
    }
}
