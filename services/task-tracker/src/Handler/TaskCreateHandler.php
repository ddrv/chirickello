<?php

declare(strict_types=1);

namespace Chirickello\TaskTracker\Handler;

use Chirickello\Package\Event\TaskAssigned\TaskAssigned;
use Chirickello\Package\Timer\TimerInterface;
use Chirickello\TaskTracker\Entity\Task;
use Chirickello\TaskTracker\Repo\TaskRepo\TaskRepo;
use Chirickello\TaskTracker\Repo\UserRepo\UserRepo;
use Chirickello\TaskTracker\Transformer\TaskTransformer;
use Exception;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TaskCreateHandler implements RequestHandlerInterface
{
    private ResponseFactoryInterface $responseFactory;
    private EventDispatcherInterface $eventDispatcher;
    private TimerInterface $timer;
    private TaskRepo $taskRepo;
    private UserRepo $userRepo;
    private TaskTransformer $transformer;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        EventDispatcherInterface $eventDispatcher,
        TimerInterface $timer,
        TaskRepo $taskRepo,
        UserRepo $userRepo,
        TaskTransformer $transformer
    ) {
        $this->responseFactory = $responseFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->timer = $timer;
        $this->taskRepo = $taskRepo;
        $this->userRepo = $userRepo;
        $this->transformer = $transformer;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $errors = [];
        try {
            $assignedTo = $this->getDeveloperId();
        } catch (Exception $e) {
            $response = $this->responseFactory
                ->createResponse(500)
                ->withHeader('content-type', ['application/json'])
            ;
            $response->getBody()->write(json_encode(['message' => $e->getMessage()]));
            return $response;
        }
        $post = $request->getParsedBody();
        $user = $request->getAttribute('user');
        $description = $post['description'] ?? null;
        if (!is_string($description)) {
            $errors['description'][] = 'description must be string';
        }
        $description = trim($description);
        $len = mb_strlen($description);
        if ($len === 0 ) {
            $errors['description'][] = 'description is required';
        }
        if ($len > 1000 ) {
            $errors['description'][] = 'description can not be longer 1000 characters';
        }
        if (!empty($errors)) {
            return $this->createErrorResponse($errors);
        }
        $authorId = $user['id'];
        $now = $this->timer->now();
        $task = new Task(
            $authorId,
            $assignedTo,
            $description,
            $now
        );

        $this->taskRepo->save($task);

        $event = new TaskAssigned($task->getId(), $task->getAssignedTo(), $this->timer->now());
        $this->eventDispatcher->dispatch($event);

        $view = $this->transformer->transform([$task])[0];

        $response = $this->responseFactory
            ->createResponse(201)
            ->withHeader('content-type', ['application/json'])
        ;
        $response->getBody()->write(json_encode($view));
        return $response;
    }

    /**
     * @return string
     * @throws Exception
     */
    private function getDeveloperId(): string
    {
        $developers = $this->userRepo->getByRole('developer');
        if (empty($developers)) {
            throw new Exception('no developers in system');
        }
        return $developers[array_rand($developers)]->getId();
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
