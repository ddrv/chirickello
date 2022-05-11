<?php
declare(strict_types=1);

use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Migration\AbstractMigration;

final class InitAuthDatabase extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $this->table('users', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', AdapterInterface::PHINX_TYPE_UUID, [
                'null' => false,
            ])
            ->addColumn('login', AdapterInterface::PHINX_TYPE_STRING, [
                'null' => false,
            ])
            ->addColumn('email', AdapterInterface::PHINX_TYPE_STRING, [
                'null' => false,
            ])
            ->addColumn('roles', AdapterInterface::PHINX_TYPE_STRING, [
                'null' => true,
            ])
            ->addIndex(
                ['email'],
                [
                    'name' => 'users_email_unique',
                    'unique' => true,
                ]
            )
            ->addIndex(
                ['login'],
                [
                    'name' => 'users_login_unique',
                    'unique' => true,
                ]
            )
            ->create()
        ;
    }
}
