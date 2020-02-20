<?php

namespace Cradle;

use Phinx\Migration\AbstractMigration;
use \Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Base class for all database migrations.
 */
abstract class Migration extends AbstractMigration
{
    /** @var \Illuminate\Database\Capsule\Manager $db */
    protected $db;

    /** @var \Illuminate\Database\Schema\Builder $schema */
    protected $schema;

    public function init()
    {
        $this->db = new Capsule;
        $this->db->addConnection([
            'driver'    => getenv('DB_DRIVER') ? getenv('DB_DRIVER') : 'mysql',
            'host'      => getenv('DB_HOST') ? getenv('DB_HOST') : 'localhost',
            'port'      => getenv('DB_PORT') ? getenv('DB_PORT') : '3306',
            'database'  => getenv('DB_NAME') ? getenv('DB_NAME') : 'test',
            'username'  => getenv('DB_USERNAME') ? getenv('DB_USERNAME') : 'root',
            'password'  => getenv('DB_PASSWORD') ? getenv('DB_PASSWORD') : '',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ]);

        $this->db->bootEloquent();
        $this->db->setAsGlobal();
        $this->schema = $this->db->schema();
    }
}
