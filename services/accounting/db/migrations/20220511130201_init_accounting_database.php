<?php
declare(strict_types=1);

use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Migration\AbstractMigration;

final class InitAccountingDatabase extends AbstractMigration
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
            ->addColumn('roles', AdapterInterface::PHINX_TYPE_STRING, [
                'null' => true,
            ])
            ->create()
        ;

        $this->table('tasks', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', AdapterInterface::PHINX_TYPE_UUID, [
                'null' => false,
            ])
            ->addColumn('title', AdapterInterface::PHINX_TYPE_TEXT, [
                'null' => false,
            ])
            ->addColumn('tax', AdapterInterface::PHINX_TYPE_INTEGER, [
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('cost', AdapterInterface::PHINX_TYPE_INTEGER, [
                'null' => false,
                'signed' => false,
            ])
            ->addIndex(
                ['tax'],
                [
                    'name' => 'tasks_tax_idx',
                    'unique' => false,
                ]
            )
            ->addIndex(
                ['cost'],
                [
                    'name' => 'tasks_cost_idx',
                    'unique' => false,
                ]
            )
            ->create()
        ;

        $this->table('user_balance', ['id' => false, 'primary_key' => ['user_id']])
            ->addColumn('user_id', AdapterInterface::PHINX_TYPE_UUID, [
                'null' => false,
            ])
            ->addColumn('amount', AdapterInterface::PHINX_TYPE_INTEGER, [
                'null' => false,
                'signed' => false,
            ])
            ->addForeignKey(
                ['user_id'],
                'users',
                'id',
                [
                    'delete' => 'RESTRICT',
                    'update' => 'CASCADE',
                ]
            )
            ->create()
        ;

        $this->table('transactions', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', AdapterInterface::PHINX_TYPE_UUID, [
                'null' => false,
            ])
            ->addColumn('user_id', AdapterInterface::PHINX_TYPE_UUID, [
                'null' => false,
            ])
            ->addColumn('debit', AdapterInterface::PHINX_TYPE_INTEGER, [
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('credit', AdapterInterface::PHINX_TYPE_INTEGER, [
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('time', AdapterInterface::PHINX_TYPE_DATETIME, [
                'null' => false,
            ])
            ->addColumn('type', AdapterInterface::PHINX_TYPE_STRING, [
                'null' => false,
            ])
            ->addColumn('comment', AdapterInterface::PHINX_TYPE_TEXT, [
                'null' => false,
            ])
            ->addForeignKey(
                ['user_id'],
                'users',
                'id',
                [
                    'delete' => 'RESTRICT',
                    'update' => 'CASCADE',
                ]
            )
            ->addIndex(
                ['user_id', 'time', 'id'],
                [
                    'name' => 'transactions_user_id_time_id_idx',
                    'unique' => false,
                ]
            )
            ->create()
        ;
    }
}
