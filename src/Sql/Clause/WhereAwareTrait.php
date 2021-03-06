<?php

/**
 * @package p3-db
 * @author  pine3ree https://github.com/pine3ree
 */

namespace P3\Db\Sql\Clause;

use Closure;
use P3\Db\Sql\Clause\Where;
use P3\Db\Sql\Clause\ConditionalClauseAwareTrait;
use P3\Db\Sql\DriverInterface;
use P3\Db\Sql\Params;
use P3\Db\Sql\Predicate;

/**
 * A trait for sql-statements that can include WHERE clauses
 */
trait WhereAwareTrait
{
    use ConditionalClauseAwareTrait;

    /** @var Where|null */
    protected $where;

    /**
     * Set WHERE conditions
     *
     * @param string|array|Predicate|Closure|Where $where
     * @return $this Fluent interface
     */
    public function where($where): self
    {
        if ($where instanceof Closure) {
            if (!isset($this->where)) {
                $this->where = new Where();
                $this->where->parent = $this;
            }
            $where($this->where);
            return $this;
        }

        $this->setConditionalClause('where', Where::class, $where);
        return $this;
    }

    private function getWhereSQL(DriverInterface $driver, Params $params): string
    {
        return $this->getConditionalClauseSQL('where', $driver, $params);
    }
}
