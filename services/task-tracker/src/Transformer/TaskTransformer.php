<?php

declare(strict_types=1);

namespace Chirickello\TaskTracker\Transformer;

use Chirickello\TaskTracker\Entity\Task;
use Chirickello\TaskTracker\Entity\User;
use Chirickello\TaskTracker\Repo\UserRepo\UserRepo;
use DateTimeZone;

class TaskTransformer
{
    private UserRepo $userRepo;
    private DateTimeZone $timezone;

    public function __construct(
        UserRepo $userRepo,
        string $timezone = 'UTC'
    ) {
        $this->userRepo = $userRepo;
        $this->timezone = new DateTimeZone($timezone);
    }

    /**
     * @param Task[] $tasks
     * @return array[]
     */
    public function transform(array $tasks): array
    {
        /** @var User $users */
        $users = [];
        /** @var string[] $userIds */
        $userIds = [];
        foreach ($tasks as $k => $task) {
            if (!$task instanceof Task) {
                unset($tasks[$k]);
            }
            $userIds[$task->getAuthorId()] = true;
            $userIds[$task->getAssignedTo()] = true;
        }
        $neededUsers = $this->userRepo->getByIds(array_keys($userIds));
        foreach ($neededUsers as $user) {
            $users[$user->getId()] = $user;
        }

        $result = [];
        foreach ($tasks as $task) {
            $taskView = [
                'id' => $task->getId(),
                'author' => $this->transformUser($users[$task->getAuthorId()]),
                'assignedTo' => $this->transformUser($users[$task->getAssignedTo()]),
                'title' => $task->getTitle(),
                'status' => $task->isCompleted() ? 'completed' : 'progress',
                'createdAt' => $task->getCreatedAt()->setTimezone($this->timezone)->format('Y-m-d\TH:i:s.vP'),
            ];
            $result[] = $taskView;
        }

        return $result;
    }

    private function transformUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'login' => $user->getLogin() ?? '_unknown_',
        ];
    }
}
