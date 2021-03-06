<?php

/**
 * @package p3-db
 * @author  pine3ree https://github.com/pine3ree
 */

namespace P3\Db\Sql\Clause;

use P3\Db\Sql;
use P3\Db\Sql\Clause\ConditionalClause;

/**
 * Represents a SQL "HAVING" clause
 */
class Having extends ConditionalClause
{
    protected static $name = Sql::HAVING;
}
