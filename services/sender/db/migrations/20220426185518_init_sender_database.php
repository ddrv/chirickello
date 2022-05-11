<?php
declare(strict_types=1);

use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Migration\AbstractMigration;

final class InitSenderDatabase extends AbstractMigration
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
                'null' => true,
            ])
            ->addColumn('email', AdapterInterface::PHINX_TYPE_STRING, [
                'null' => true,
            ])
            ->create()
        ;
    }
}
