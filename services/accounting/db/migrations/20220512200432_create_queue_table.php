<?php
declare(strict_types=1);

use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Migration\AbstractMigration;

final class CreateQueueTable extends AbstractMigration
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
        $this->table('queue', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', AdapterInterface::PHINX_TYPE_INTEGER, [
                'null' => false,
                'signed' => false,
                'identity' => true,
            ])
            ->addColumn('name', AdapterInterface::PHINX_TYPE_STRING, [
                'null' => false,
            ])
            ->addColumn('message', AdapterInterface::PHINX_TYPE_TEXT, [
                'null' => false,
            ])
            ->addColumn('try_after', AdapterInterface::PHINX_TYPE_TIMESTAMP, [
                'null' => true,
            ])
            ->addIndex(
                ['name', 'try_after'],
                [
                    'name' => 'queue_pull_idx',
                    'unique' => false,
                ]
            )
            ->addIndex(
                ['try_after', 'id'],
                [
                    'name' => 'queue_sorting_idx',
                    'unique' => false,
                ]
            )
            ->create()
        ;
    }
}
