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
            $fields = ['id'];
            $set = [];
            $params = ['id' => $user->getId()];
            $login = $user->getLogin();
            if (!is_null($login)) {
                $fields[] = 'login';
                $set[] =  'login = :login';
                $params['login'] = $login;
            }
            $roles = $user->getRoles();
            if (!empty($roles)) {
                $fields[] = 'roles';
                $set[] =  'roles = :roles';
                $params['roles'] = ',' . implode(',', $roles) . ',';
            }

            if (empty($set)) {
                $this->db->commit();
                return;
            }
            $sql = 'INSERT INTO users (' . implode(', ', $fields) . ') VALUES(:' . implode(', :', $fields) . ')'
                . ' ON CONFLICT (id) DO UPDATE SET ' . implode(', ', $set) . ' WHERE id = :id;'
            ;

            $st = $this->db->prepare($sql);
            $st->execute($params);
            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw new StorageException('storage error', 0, $exception);
        }
    }

    private function hydrateUser(array $row): User
    {
        $roles = explode(',', $row['roles'] ?? ',,');
        array_shift($roles);
        array_pop($roles);
        $roles = array_unique($roles);

        return new User(
            (string)$row['id'],
            empty(trim((string)$row['login'])) ? null : (string)$row['login'],
            empty($roles) ? null : $roles
        );
    }
}
