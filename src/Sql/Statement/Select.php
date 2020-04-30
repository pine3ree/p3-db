<?php

/**
 * @package     package
 * @subpackage  package-subpackage
 * @author      pine3ree https://github.com/pine3ree
 */

namespace P3\Db\Sql\Statement;

use InvalidArgumentException;
use P3\Db\Sql;
use P3\Db\Sql\Condition\Having;
use P3\Db\Sql\Condition\On;
use P3\Db\Sql\Condition\Where;
use P3\Db\Sql\Literal;
use P3\Db\Sql\Predicate;
use P3\Db\Sql\PredicateSet;
use P3\Db\Sql\Statement\DML;
use P3\Db\Sql\Statement\Traits\ConditionAwareTrait;
use PDO;
use RuntimeException;

/**
 * This class represents a SELECT sql-statement expression
 *
 * @property-read Where|null $where The Where clause if any
 * @property-read Having|null $where The Having clause if any
 */
class Select extends DML
{
    use ConditionAwareTrait;

    /** @var string|null */
    protected $quantifier;

    /** @var string[] */
    protected $columns = [
        Sql::ASTERISK => Sql::ASTERISK,
    ];

    /** @var Where|null */
    protected $where;

    /** @var array */
    protected $join;

    /** @var array */
    protected $groupBy = [];

    /** @var Having|null */
    protected $having;

    /** @var array */
    protected $orderBy = [];

    /** @var inti|null */
    protected $limit;

    /** @var int|null */
    protected $offset;

    public function __construct($columns = null, $table = null)
    {
        if (!empty($columns)) {
            $this->columns($columns);
        }
        if (!empty($table)) {
            $this->from($table);
        }
    }

    /**
     * @param string $quantifier
     * @return $this
     */
    public function quantifier(string $quantifier): self
    {
        $quantifier = strtoupper($quantifier);
        if (in_array($quantifier, [Sql::DISTINCT, Sql::ALL], true)) {
            $this->quantifier = $quantifier;
        }

        return $this;
    }

    /**
     * @param string $quantifier
     * @return $this
     */
    public function distinct(): self
    {
        $this->quantifier = Sql::DISTINCT;
        return $this;
    }

    /**
     *
     * @param type $columns
     * @return $this
     */
    public function columns($columns): self
    {
        if (empty($columns)) {
            return $this;
        }

        if (Sql::ASTERISK === $columns) {
            $this->columns = [
                Sql::ASTERISK => Sql::ASTERISK,
            ];
        }

        if (!is_array($columns)) {
            throw new InvalidArgumentException(sprintf(
                "The SELECT columns argumen must be either the ASTERISK string"
                . " or an array of column names, '%s' provided!",
                gettype($columns)
            ));
        }

        $this->columns = $columns;

        unset($this->sql, $this->sqls['columns']);

        return $this;
    }

    private function getColumnsSQL(): string
    {
        if (isset($this->sqls['columns'])) {
            return $this->sqls['columns'];
        }

        $sqls = [];
        foreach ($this->columns as $alias => $column) {
            if ($column === Sql::ASTERISK ) {
                $column_sql = $this->alias ? $this->quoteAlias($this->alias) . ".*" : "*";
            } else {
                $column_sql = $column instanceof Literal
                    ? (string)$column
                    : $this->normalizeColumn($column);
                if (!is_numeric($alias)) {
                    $column_sql .= " AS " . $this->quoteAlias($alias);
                }
            }
            $sqls[] = $column_sql;
        }

        $sql = trim(implode(", ", $sqls));
        $this->sqls['columns'] = $sql;

        return $sql;
    }

    /**
     * Set the SELECT FROM table
     *
     * @param string|array $table
     * @param string|null $alias
     * @return \self
     */
    public function from($table, string $alias = null): self
    {
         parent::setTable($table, $alias);
         return $this;
    }

    public function join(string $table, string $alias, $cond = null): self
    {
        return $this->addJoin(Sql::JOIN_AUTO, $table, $alias, $cond);
    }

    public function innerJoin(string $table, string $alias, $cond = null): self
    {
        return $this->addJoin(Sql::JOIN_INNER, $table, $alias, $cond);
    }

    public function leftJoin(string $table, string $alias, $cond = null): self
    {
        return $this->addJoin(Sql::JOIN_LEFT, $table, $alias, $cond);
    }

    public function rightJoin(string $table, string $alias, $cond = null): self
    {
        return $this->addJoin(Sql::JOIN_RIGHT, $table, $alias, $cond);
    }

    /**
     * Add a join specification to this statement
     *
     * @param string $type The join type (LEFT, RIGHT, INNER, ...)
     * @param string $table The join table name
     * @param string $alias The join table alias
     * @param On|PredicateSet|Predicate|Literal|Predicate\Literal|array|string $cond
     *      The join conditional usually an ON clause, but may be changed using Literal classes
     * @return $this
     */
    protected function addJoin(string $type, string $table, string $alias, $cond = null): self
    {
        if (! $cond instanceof On
            && ! $cond instanceof Literal
        ) {
            $cond = new On(Sql::AND, $cond);
        }

        $this->join[] = [
            'type'  => $type,
            'table' => $table,
            'alias' => $alias,
            'cond'  => $cond,
        ];

        unset($this->sql, $this->sqls['join']);

        return $this;
    }

    protected function getJoinSQL(): string
    {
        if (empty($this->join)) {
            return '';
        }

        if (isset($this->sqls['join'])) {
            return $this->sqls['join'];
        }

        $sqls = [];
        foreach ($this->join as $join) {
            $type  = isset(Sql::JOIN_TYPES[$join['type']]) ? $join['type'] : '';
            $table = $this->quoteIdentifier($join['table']);
            $alias = $this->quoteAlias($join['alias']);
            $cond  = $join['cond']->getSQL(false);

            if ($join['cond']->hasParams()) {
                $this->importParams($join['cond']);
            }

            $sqls[] = trim("{$type} JOIN {$table} {$alias} {$cond}");
        }

        $this->sqls['join'] = $sql = trim(implode(" ", $sqls));
        return $sql;
    }

    /**
     * Add WHERE conditions
     *
     * @param string|array|Predicate|Where $where
     * @return $this
     */
    public function where($where): self
    {
        $this->setCondition('where', Where::class, $where);
        return $this;
    }

    protected function getWhereSQL(bool $stripParentheses = false): string
    {
        return $this->getConditionSQL('where', $stripParentheses);
    }

    /**
     *
     * @param sring|string[] $groupBy
     * @param bool $replace
     * @return $this
     * @throws InvalidArgumentException
     */
    public function groupBy($groupBy, bool $replace = false): self
    {
        if ($replace) {
            $this->groupBy = [];
        }

        if (is_array($groupBy)) {
            foreach ($this->groupBy as $identifier) {
                $this->groupBy($identifier);
            }
            return $this;
        }

        if (!is_string($groupBy)) {
            throw new InvalidArgumentException(sprintf(
                "The `groupBy` argument must be either a string or an array of"
                . " string identifiers, `%s` provided",
                gettype($groupBy)
            ));
        }

        $this->groupBy[] = $groupBy;

        return $this;
    }

    protected function getGroupBySQL(): string
    {
        if (empty($this->groupBy)) {
            return '';
        }

        return "GROUP BY " . implode(", ", $this->groupBy);
    }

    /**
     * Add WHERE conditions
     *
     * @param string|array|Predicate|Having $having
     * @return $this
     */
    public function having($having): self
    {
        $this->setCondition('having', Having::class, $having);
        return $this;
    }

    protected function getHavingSQL(bool $stripParentheses = false): string
    {
        return $this->getConditionSQL('having', $stripParentheses);
    }

    /**
     *
     * @param type $orderBy
     * @param null|string|true $sortOrReplace Set the default sort or the replace flag
     * @return \self
     */
    public function orderBy($orderBy, $sortOrReplace = null): self
    {
        if (true === $sortOrReplace) {
            $this->orderBy = [];
        }

        $sort = is_string($sortOrReplace) ? strtoupper($sortOrReplace) : null;

        $orderBy = $this->nomalizeOrderBy($orderBy, $sort);
        if (empty($orderBy)) {
            return $this;
        }

        $this->orderBy += $orderBy;

        return $this;
    }

    private function nomalizeOrderBy($orderBy, string $sort = null): array
    {
        if (is_string($orderBy)) {
            if (false === strpos($orderBy, ',')) {
                $orderBy = [$orderBy];
            } else {
                $orderBy = array_map('trim', explode(',', $orderBy));
            }
        }

        if (!is_array($orderBy)) {
            throw new \InvalidArgumentException(
                "The ORDER BY options must be either an array or a string!"
            );
        }

        $normalized = [];

        foreach ($orderBy as $identifier => $direction) {
            if (is_numeric($identifier)) {
                $identifier = $direction;
                $direction  = $sort;
            }


            $parts = array_map('trim', explode(' ', $identifier));
            $identifier = $parts[0];
            $direction  = $parts[1] ?? $direction ?? Sql::ASC;
            $direction  = $direction === Sql::DESC ? $direction : Sql::ASC;

            $normalized[$identifier] = $direction;
        }

        return $normalized;
    }

    private function getOrderBySQL(): string
    {
        if (empty($this->orderBy)) {
            return '';
        }

        if (isset($this->sqls['order'])) {
            return $this->sqls['order'];
        }

        $sql = [];
        foreach ($this->orderBy as $identifier => $direction) {
            // do not quote identifier or alias, do it programmatically
            $sql[] = "{$identifier} {$direction}";
        }

        $this->sqls['order'] = $sql = "ORDER BY " . implode(", ", $sql);

        return $sql;
    }

    public function limit(int $limit): self
    {
        $this->limit = max(0, $limit);
        unset($this->sqls['limit']);

        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = max(0, $offset);
        unset($this->sqls['limit']);

        return $this;
    }

    private function getLimitSQL(): string
    {
        if (isset($this->sqls['limit'])) {
            return $this->sqls['limit'];
        }

        $sql = '';
        if ($this->limit > 0) {
            $limit = $this->createNamedParam($this->limit, PDO::PARAM_INT);
            $sql .= "LIMIT {$limit}";
        }
        if ($this->offset > 0) {
            $offset = $this->createNamedParam($this->offset, PDO::PARAM_INT);
            $sql .= "OFFSET {$offset}";
        }

        return $this->sqls['limit'] = $sql;
    }

    public function getSQL(bool $stripConditionsParentheses = false): string
    {
        if (isset($this->sql)) {
            return $this->sql;
        }

        $base_sql = $this->getBaseSQL();
        $clauses_sql = $this->getClausesSQL($stripConditionsParentheses);

        $this->sql = rtrim("{$base_sql} {$clauses_sql}");

        return $this->sql;
    }

    protected function getBaseSQL(): string
    {
        if (empty($this->table)) {
            throw new RuntimeException(
                "The SELECT FROM table has not been defined!"
            );
        }

        $select = "SELECT";
        if ($this->quantifier) {
            $select .= " {$this->quantifier}";
        }

        $columns = $this->getColumnsSQL();

        $table = $this->quoteIdentifier($this->table);
        if (!empty($this->alias) && $alias = $this->quoteAlias($this->alias)) {
            $table .= " {$alias}";
        }

        return "{$select} {$columns} FROM {$table}";
    }

    private function getClausesSQL(bool $stripConditionsParentheses = false): string
    {
        $sqls = [];

        $sqls[] = $this->getJoinSQL($stripConditionsParentheses);
        $sqls[] = $this->getWhereSQL($stripConditionsParentheses);
        $sqls[] = $this->getGroupBySQL();
        $sqls[] = $this->getHavingSQL($stripConditionsParentheses);
        $sqls[] = $this->getOrderBySQL();
        $sqls[] = $this->getLimitSQL();

        foreach ($sqls as $index => $sql) {
            if ($this->isEmptySQL($sql)) {
                unset($sqls[$index]);
            }
        }

        return implode(" ", $sqls);
    }

    public function __get(string $name)
    {
        if ('where' === $name) {
            return $this->where;
        };
        if ('having' === $having) {
            return $this->having;
        };
    }
}
