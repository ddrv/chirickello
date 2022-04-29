<?php

declare(strict_types=1);

namespace Chirickello\Auth\Repo\UserRepo;

use Chirickello\Auth\Entity\User;
use Chirickello\Auth\Exception\EmailExistsException;
use Chirickello\Auth\Exception\LoginExistsException;
use Chirickello\Auth\Exception\StorageException;
use Chirickello\Auth\Exception\UserNotFoundException;
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
    public function getByLogin(string $login): User
    {
        $st = $this->db->prepare('SELECT * FROM users WHERE login = ?;');
        $st->execute([$login]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (empty($row)) {
            throw new UserNotFoundException(sprintf('user with login %s not found', $login));
        }
        return $this->hydrateUser($row);
    }

    /**
     * @inheritDoc
     */
    public function getAll(): iterable
    {
        $st = $this->db->prepare('SELECT * FROM users ORDER BY login;');
        $st->execute();
        $result = [];
        while($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $this->hydrateUser($row);
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function save(User $user): void
    {
        if ($user->isNew()) {
            $this->create($user);
        } else {
            $this->update($user);
        }
    }

    /**
     * @param User $user
     * @return void
     * @throws EmailExistsException
     * @throws LoginExistsException
     * @throws StorageException
     */
    private function create(User $user): void
    {
        $login = $user->getLogin();
        $email = $user->getEmail();
        $this->db->beginTransaction();
        try {
            $sql = 'SELECT login, email FROM users WHERE login = ? OR email = ?;';
            $st = $this->db->prepare($sql);
            $st->execute([$login, $email]);
            while ($row = $st->fetch()) {
                if (!empty($row)) {
                    if ($row['email'] === $email) {
                        throw new EmailExistsException(sprintf('email %s exists', $email));
                    }
                    if ($row['login'] === $login) {
                        throw new LoginExistsException(sprintf('login %s exists', $login));
                    }
                }
            }

            $id = $this->generateId();
            $sql = 'INSERT INTO users (id, login, email, roles) VALUES (?, ?, ?, ?);';
            $st = $this->db->prepare($sql);
            $st->execute([
                $id,
                $login,
                $email,
                implode(',', $user->getRoles()),
            ]);
            $this->db->commit();
            $user->setId($id);
        } catch (EmailExistsException|LoginExistsException $exception) {
            $this->db->rollBack();
            throw $exception;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw new StorageException('storage exception', 0, $exception);
        }
    }

    /**
     * @param User $user
     * @return void
     * @throws EmailExistsException
     * @throws LoginExistsException
     * @throws StorageException
     */
    private function update(User $user): void
    {
        $id = $user->getId();
        $login = $user->getLogin();
        $email = $user->getEmail();
        $this->db->beginTransaction();
        try {
            $sql = 'SELECT login, email FROM users WHERE id != ? AND (login = ? OR email = ?);';
            $st = $this->db->prepare($sql);
            $st->execute([$id, $login, $email]);
            while ($row = $st->fetch()) {
                if (!empty($row)) {
                    if ($row['email'] === $email) {
                        throw new EmailExistsException(sprintf('email %s exists', $email));
                    }
                    if ($row['login'] === $login) {
                        throw new LoginExistsException(sprintf('login %s exists', $login));
                    }
                }
            }

            $sql = 'UPDATE users SET login = ?, email = ?, roles = ? WHERE id = ?;';
            $st = $this->db->prepare($sql);
            $st->execute([
                $login,
                $email,
                implode(',', $user->getRoles()),
                $id,
            ]);
            $this->db->commit();
        } catch (EmailExistsException|LoginExistsException $exception) {
            $this->db->rollBack();
            throw $exception;
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

        $user = new User($login, $email, $id);
        $roles = explode(',', $row['roles']);
        foreach ($roles as $role) {
            $role = trim(strtolower($role));
            if ($role === '') {
                continue;
            }
            $user->addRole($role);
        }
        return $user;
    }

    private function generateId(): string
    {
        $st = $this->db->prepare('SELECT * FROM users WHERE id = ?;');
        do {
            $uuid = $this->generateUuid();
            $st->execute([$uuid]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
        } while (!empty($row));
        return $uuid;
    }

    private function generateUuid(): string
    {
        // https://www.php.net/manual/en/function.uniqid.php#94959
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,

            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }
}
