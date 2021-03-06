<?php

/**
 * @package p3-db
 * @author  pine3ree https://github.com/pine3ree
 */

namespace P3\Db\Sql\Driver;

use PDO;
use P3\Db\Sql\Driver;

/**
 * Sqlite sql-driver
 */
class Sqlite extends Driver
{
    public function __construct(PDO $pdo = null)
    {
        parent::__construct($pdo, '"', '"', "'");
    }
}
