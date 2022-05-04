<?php

declare(strict_types=1);

namespace Chirickello\TaskTracker\Repo\UserRepo;

use Chirickello\TaskTracker\Entity\User;
use Chirickello\TaskTracker\Exception\StorageException;
use Chirickello\TaskTracker\Exception\UserNotFoundException;
use PDO;
use Throwable;

class UserPdoRepo implements UserRepo
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * @inheritDoc
     */
    public function getById(string $id): User
    {
        $sql = 'SELECT * FROM users WHERE id = ?;';
        $st = $this->db->prepare($sql);
        $st->execute([$id]);
        $row = $st->fetch();
        if (empty($row)) {
            throw new UserNotFoundException();
        }
        return $this->hydrateUser($row);
    }

    /**
     * @inheritDoc
     */
    public function getByIds(array $ids): iterable
    {
        if (empty($ids)) {
            return [];
        }
        $ids = array_unique($ids);
        $in = array_fill(0, count($ids), '?');
        $sql = 'SELECT * FROM users WHERE id IN (' . (implode(', ', $in)) . ');';
        $st = $this->db->prepare($sql);
        $st->execute(array_values($ids));
        $result = [];
        while ($row = $st->fetch()) {
            $user = $this->hydrateUser($row);
            $result[$user->getId()] = $user;
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getByRole(string $role): iterable
    {
        $sql = 'SELECT * FROM users WHERE roles LIKE ?';
        $st = $this->db->prepare($sql);
        $st->execute(['%,' . $role . ',%']);
        $result = [];
        while ($row = $st->fetch()) {
            $user = $this->hydrateUser($row);
            $result[$user->getId()] = $user;
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function save(User $user): void
    {
        $this->db->beginTransaction();
        try {
            $sql = 'SELECT * FROM users WHERE id = ?;';
            $st = $this->db->prepare($sql);
            $st->execute([$user->getId()]);
            $row = $st->fetch();
            if (empty($row)) {
                $this->create($user);
            } else {
                $this->update($user, $row);
            }
            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw new StorageException('storage error', 0, $exception);
        }
    }

    private function create(User $user): void
    {
        $sql = 'INSERT INTO users (id, login, roles) VALUES (?, ?, ?);';
        $st = $this->db->prepare($sql);
        $st->execute([
            $user->getId(),
            $user->getLogin(),
            ',' . implode(',', $user->getRoles()) . ','
        ]);
    }

    private function update(User $user, array $original): void
    {
        $set = [];
        $params = [];
        if ($user->getLogin() !== $original['login']) {
            $set[] = 'login = ?';
            $params[] = $user->getLogin();
        }
        $roles = ',' . implode(',', $user->getRoles()) . ',';
        if ($roles !== $original['roles']) {
            $set[] = 'roles = ?';
            $params[] = $roles;
        }
        if (empty($set)) {
            return;
        }
        $sql = 'UPDATE users SET ' . (implode(', ', $set)) . ' WHERE id = ?;';
        $params[] = $user->getId();
        $st = $this->db->prepare($sql);
        $st->execute($params);
    }

    private function hydrateUser(array $row): User
    {
        $roles = explode(',', $row['roles']);
        array_shift($roles);
        array_pop($roles);
        $roles = array_unique($roles);

        return new User(
            (string)$row['id'],
            empty(trim($row['login'])) ? null : (string)$row['login'],
            empty($roles) ? null : $roles
        );
    }
}
