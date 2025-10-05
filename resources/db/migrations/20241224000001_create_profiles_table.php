<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateProfilesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('profiles', ['id' => false, 'primary_key' => 'id']);

        $table->addColumn('id', 'uuid')
              ->addColumn('name', 'string', ['limit' => 255])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'timestamp', ['null' => true])
              ->addColumn('deleted_at', 'timestamp', ['null' => true])
              ->create();

        // Set the default value for id column using raw SQL
        $this->execute('ALTER TABLE profiles ALTER COLUMN id SET DEFAULT gen_random_uuid()');
    }
}
