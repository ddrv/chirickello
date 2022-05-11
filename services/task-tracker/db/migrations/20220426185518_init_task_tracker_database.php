<?php
declare(strict_types=1);

use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Migration\AbstractMigration;

final class InitTaskTrackerDatabase extends AbstractMigration
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
            ->addColumn('title', AdapterInterface::PHINX_TYPE_STRING, [
                'null' => false,
            ])
            ->addColumn('is_completed', AdapterInterface::PHINX_TYPE_BOOLEAN, [
                'null' => false,
            ])
            ->addColumn('author_id', AdapterInterface::PHINX_TYPE_UUID, [
                'null' => false,
            ])
            ->addColumn('assigned_to', AdapterInterface::PHINX_TYPE_UUID, [
                'null' => false,
            ])
            ->addColumn('created_at', AdapterInterface::PHINX_TYPE_DATETIME, [
                'null' => false,
            ])
            ->addForeignKey(
                ['author_id'],
                'users',
                'id',
                [
                    'delete' => 'RESTRICT',
                    'update' => 'CASCADE',
                ]
            )
            ->addForeignKey(
                ['assigned_to'],
                'users',
                'id',
                [
                    'delete' => 'RESTRICT',
                    'update' => 'CASCADE',
                ]
            )
            ->addIndex(
                ['assigned_to'],
                [
                    'name' => 'tasks_assigned_to_idx',
                    'unique' => false,
                ]
            )
            ->addIndex(
                ['author_id'],
                [
                    'name' => 'tasks_author_id_idx',
                    'unique' => false,
                ]
            )
            ->addIndex(
                ['created_at'],
                [
                    'name' => 'tasks_created_at_idx',
                    'unique' => false,
                ]
            )
            ->addIndex(
                ['is_completed'],
                [
                    'name' => 'tasks_is_completed_idx',
                    'unique' => false,
                ]
            )
            ->create()
        ;
    }
}
