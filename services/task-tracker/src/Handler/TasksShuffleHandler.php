<?php

declare(strict_types=1);

namespace Chirickello\TaskTracker\Handler;

use Chirickello\Package\Timer\TimerInterface;
use Chirickello\TaskTracker\Entity\Task;
use Chirickello\TaskTracker\Repo\Paginator;
use Chirickello\TaskTracker\Repo\TaskRepo\TaskFilter;
use Chirickello\TaskTracker\Repo\TaskRepo\TaskRepo;
use Chirickello\TaskTracker\Repo\UserRepo\UserRepo;
use Exception;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TasksShuffleHandler implements RequestHandlerInterface
{
    private ResponseFactoryInterface $responseFactory;
    private EventDispatcherInterface $eventDispatcher;
    private TimerInterface $timer;
    private TaskRepo $taskRepo;
    private UserRepo $userRepo;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        EventDispatcherInterface $eventDispatcher,
        TimerInterface $timer,
        TaskRepo $taskRepo,
        UserRepo $userRepo
    ) {
        $this->responseFactory = $responseFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->timer = $timer;
        $this->taskRepo = $taskRepo;
        $this->userRepo = $userRepo;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $developerIds = $this->getDeveloperIds();
        } catch (Exception $e) {
            $response = $this->responseFactory
                ->createResponse(500)
                ->withHeader('content-type', ['application/json'])
            ;
            $response->getBody()->write(json_encode(['message' => $e->getMessage()]));
            return $response;
        }

        $filter = new TaskFilter();
        $filter->isCompleted = false;
        $page = 1;
        do {
            $paginator = new Paginator($page, 1);
            $tasks = $this->taskRepo->getFiltered($filter, $paginator);
            foreach ($tasks as $task) {
                $this->reassignTask($task, $developerIds[array_rand($developerIds)]);
            }
            $page += 1;
        } while (!empty($tasks));

        $response = $this->responseFactory->createResponse()->withHeader('content-type', ['application/json']);
        $response->getBody()->write(json_encode(['ok' => true]));
        return $response;
    }

    private function getDeveloperIds(): array
    {
        $ids = [];
        $developers = $this->userRepo->getByRole('developer');
        if (empty($developers)) {
            throw new Exception('no developers in system');
        }
        foreach ($developers as $developer) {
            $ids[] = $developer->getId();
        }
        return $ids;
    }

    private function reassignTask(Task $task, string $userId): void
    {
        $task->assign($userId);
        if ($task->isAssignedToChanged()) {
            // todo send event `task.assigned`
        }
        $this->taskRepo->save($task);
    }
}
