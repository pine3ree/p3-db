<?php

/**
 * @package     package
 * @subpackage  package-subpackage
 * @author      pine3ree https://github.com/pine3ree
 */

namespace P3\Db\Sql\Predicate;

use P3\Db\Sql\Predicate;

/**
 * This class represents a sql expression with parameter markers
 */
class Expression extends Predicate
{
    /**
     * @pvar string The literal SQL expression
     */
    private $expression;

    public function __construct(string $expression, array $params = [])
    {
        $this->sql = trim($expression);
        foreach ($params as $key => $value) {
            $this->setParam($expression, $value);
        }
    }

    public function getSQL(): string
    {
        return $this->sql ?? '';
    }
}
