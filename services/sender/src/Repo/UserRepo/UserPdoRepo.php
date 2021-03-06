<?php

declare(strict_types=1);

namespace Chirickello\Sender\Repo\UserRepo;

use Chirickello\Sender\Entity\User;
use Chirickello\Sender\Exception\StorageException;
use Chirickello\Sender\Exception\UserNotFoundException;
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
        $st = $this->db->prepare('SELECT * FROM users WHERE id = ?;');
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (empty($row)) {
            throw new UserNotFoundException(sprintf('user with id %s not found', $id));
        }
        return $this->hydrateUser($row);
    }

    /**
     * @inheritDoc
     */
    public function save(User $user): void
    {
        try {
            $this->getById($user->getId());
            $this->update($user);
        } catch (UserNotFoundException $e) {
            $this->create($user);
        }
        $user->flush();
    }

    /**
     * @inheritDoc
     */
    public function delete(User $user): void
    {
        try {
            $sql = 'DELETE FROM users WHERE id = ?;';
            $st = $this->db->prepare($sql);
            $st->execute([
                $user->getId(),
            ]);
            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw new StorageException('storage exception', 0, $exception);
        }
    }

    /**
     * @param User $user
     * @return void
     * @throws StorageException
     */
    private function create(User $user): void
    {
        $this->db->beginTransaction();
        $id = $user->getId();
        $login = $user->getLogin();
        $email = $user->getEmail();
        try {
            $sql = 'INSERT INTO users (id, login, email) VALUES (?, ?, ?);';
            $st = $this->db->prepare($sql);
            $st->execute([
                $id,
                $login,
                $email,
            ]);
            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw new StorageException('storage exception', 0, $exception);
        }
    }

    /**
     * @param User $user
     * @return void
     * @throws StorageException
     */
    private function update(User $user): void
    {
        if (!$user->isChanged()) {
            return;
        }
        $update = [];
        $params = [];
        if ($user->isEmailChanged()) {
            $update[] = 'email = ?';
            $params[] = $user->getEmail();
        }
        if ($user->isLoginChanged()) {
            $update[] = 'login = ?';
            $params[] = $user->getLogin();
        }
        $params[] = $user->getId();
        $sql = 'UPDATE users SET ' . (implode(', ', $update)) . ' WHERE id = ?';

        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare($sql);
            $st->execute($params);
            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw new StorageException('storage exception', 0, $exception);
        }
    }

    private function hydrateUser(array $row): User
    {
        $id = $row['id'];
        $login = $row['login'];
        $email = $row['email'];
        return new User($id, $login, $email);
    }
}
