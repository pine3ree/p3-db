<?php

/**
 * @package     package
 * @subpackage  package-subpackage
 * @author      pine3ree https://github.com/pine3ree
 */

namespace P3\Db\Sql\Statement\Traits;

use P3\Db\Sql;
use P3\Db\Sql\Clause;
use P3\Db\Sql\Clause\Having;
use P3\Db\Sql\Clause\On;
use P3\Db\Sql\Clause\Where;
use P3\Db\Sql\Predicate;
use P3\Db\Sql\PredicateSet;
use P3\Db\Sql\Statement;

/**
 * A trait for sql-statements that can include WHERE, HAVING and ON clauses
 */
trait ClauseAwareTrait
{
//    /** @var Where|null */
//    protected $where;
//
//    /** @var Having|null */
//    protected $having;
//
//    /** @var On|null */
//    protected $on;
//
//    /** @var string|array|Predicate|Where| */
//    public function where($where): self
//    {
//        return $this->setClause('where', Where::class, $where);
//    }
//
//    protected function getWhereSQL(bool $stripParentheses = false): string
//    {
//        return $this->getClauseSQL('where', $stripParentheses);
//    }
//
//    /** @var string|array|Predicate|Having| */
//    public function having($having): self
//    {
//        return $this->setClause('having', Having::class, $having);
//    }
//
//    protected function getHavingSQL(bool $stripParentheses = false): string
//    {
//        return $this->getClauseSQL('having', $stripParentheses);
//    }
//
//    /** @var string|array|Predicate|Having| */
//    public function on($on): self
//    {
//        return $this->setClause('on', On::class, $on);
//    }
//
//    protected function getOnSQL(bool $stripParentheses = false): string
//    {
//        return $this->getClauseSQL('on', $stripParentheses);
//    }

    /**
     * Define a clause
     *
     * @param string $property
     * @param string $fqcn
     * @param string|array|Predicate|PredicateSet $clause
     */
    private function setClause($property, $fqcn, $clause): self
    {
        if (isset($this->{$property})) {
            throw new \RuntimeException(
                "Clause of class `{$fqcn}` for property `{$property}` is already set!"
            );
        }

        if (is_array($clause)) {
            if (count($clause) === 1
                && isset(PredicateSet::COMB_ID[$comb_id = key($clause)])
            ) {
                $clause = new $fqcn($comb_id, current($clause));
            } else {
                $clause = new $fqcn(Sql::AND, $clause);
            }
        } elseif (! $clause instanceof $fqcn) {
            $clause = new $fqcn(Sql::AND, $clause);
        }

        $this->{$property} = $clause;
        unset($this->sql, $this->sqls[$property]);

        return $this;
    }

    /**
     * Return the compiled SQL string for a given clause property
     *
     * @param string $property The sql-statement property in which the clause is stored
     * @return string
     */
    private function getClauseSQL(string $property, bool $stripParentheses = false): string
    {
        if (!isset($this->{$property})) {
            return '';
        }

        $sql = $this->{$property}->getSQL($stripParentheses);
        if ($this instanceof Statement) {
            $this->importParams($this->{$property});
        }

        return $sql;
    }



    /**
     * Import parameters and types from inner predicate
     *
     * @param Predicate $predicate
     */
    private function importParams(Clause $clause)
    {
        foreach ($clause->getParams() as $index => $value) {
            $this->params[$index] = $value;
        }
        foreach ($clause->getParamsTypes() as $index => $type) {
            $this->params_types[$index] = $type;
        }
    }
}