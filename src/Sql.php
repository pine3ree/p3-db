<?php

/**
 * @package p3-db
 * @author  pine3ree https://github.com/pine3ree
 */

namespace P3\Db;

use P3\Db\Sql\Statement\Delete;
use P3\Db\Sql\Statement\Insert;
use P3\Db\Sql\Statement\Select;
use P3\Db\Sql\Statement\Update;
use InvalidArgumentException;

use function func_num_args;
use function gettype;
use function is_string;
use function sprintf;
use function strtoupper;
use function trim;

/**
 * Class Sql exposes common SQL constants and utility methods
 */
class Sql
{
    /**
     * DML statements
     */
    public const SELECT = 'SELECT';
    public const INSERT = 'INSERT';
    public const UPDATE = 'UPDATE';
    public const DELETE = 'DELETE';

    public const DML_STATEMENTS = [
        self::SELECT => self::SELECT,
        self::INSERT => self::INSERT,
        self::UPDATE => self::UPDATE,
        self::DELETE => self::DELETE,
    ];

    /**
     * DDL statements
     */
    public const CREATE = 'CREATE';
    public const ALTER  = 'ALTER';
    public const DROP   = 'DROP';

    public const DDL_STATEMENTS = [
        self::CREATE => self::CREATE,
        self::ALTER  => self::ALTER,
        self::DROP   => self::DROP,
    ];

    /**
     * Clauses
     */
    public const WHERE    = 'WHERE';
    public const JOIN     = 'JOIN';
    public const ON       = 'ON';
    public const GROUP_BY = 'GROUP BY';
    public const HAVING   = 'HAVING';
    public const ORDER_BY = 'ORDER BY';
    public const LIMIT    = 'LIMIT';
    public const OFFSET   = 'OFFSET';

    public const CLAUSES = [
        self::WHERE    => self::WHERE,
        self::JOIN     => self::JOIN,
        self::ON       => self::ON,
        self::GROUP_BY => self::GROUP_BY,
        self::HAVING   => self::HAVING,
        self::ORDER_BY => self::ORDER_BY,
        self::LIMIT    => self::LIMIT,
        self::OFFSET   => self::OFFSET,
    ];

    /**
     * Join clause types
     */
    public const JOIN_AUTO          = '';
    public const JOIN_INNER         = 'INNER';
    public const JOIN_CROSS         = 'CROSS';
    public const JOIN_LEFT          = 'LEFT';
    public const JOIN_RIGHT         = 'RIGHT';
    public const JOIN_STRAIGHT      = 'STRAIGHT_JOIN';
    public const JOIN_NATURAL       = 'NATURAL';
    public const JOIN_NATURAL_LEFT  = 'NATURAL LEFT';
    public const JOIN_NATURAL_RIGHT = 'NATURAL RIGHT';
    public const USING              = 'USING';

    public const JOIN_TYPES = [
        self::JOIN_AUTO          => self::JOIN_AUTO,
        self::JOIN_INNER         => self::JOIN_INNER,
        self::JOIN_CROSS         => self::JOIN_CROSS,
        self::JOIN_LEFT          => self::JOIN_LEFT,
        self::JOIN_RIGHT         => self::JOIN_RIGHT,
        self::JOIN_STRAIGHT      => self::JOIN_STRAIGHT,
        self::JOIN_NATURAL       => self::JOIN_NATURAL,
        self::JOIN_NATURAL_LEFT  => self::JOIN_NATURAL_LEFT,
        self::JOIN_NATURAL_RIGHT => self::JOIN_NATURAL_RIGHT,
    ];

    /**
     * ORDER BY directions
     */
    public const ASC  = 'ASC';
    public const DESC = 'DESC';

    public const SORT = [
        self::ASC  => self::ASC,
        self::DESC => self::DESC,
    ];

    /**
     * Comparison operators...
     */
    public const EQUAL              = '=';
    public const NOT_EQUAL          = '!=';
    public const NOT_EQUAL_ANSI     = '<>'; // NOT EQUAL ANSI-SQL
    public const LESS_THAN          = '<';
    public const LESS_THAN_EQUAL    = '<=';
    public const GREATER_THAN_EQUAL = '>=';
    public const GREATER_THAN       = '>';
    /**
     * ...and their short aliases
     */
    public const EQ  = self::EQUAL;
    public const NEQ = self::NOT_EQUAL;
    public const NE  = self::NOT_EQUAL_ANSI;
    public const LT  = self::LESS_THAN;
    public const LTE = self::LESS_THAN_EQUAL;
    public const GTE = self::GREATER_THAN_EQUAL;
    public const GT  = self::GREATER_THAN;

    // valid comparison operators excluding boolean
    public const COMPARISON_OPERATORS = [
        self::EQ  => self::EQ,
        self::NEQ => self::NEQ,
        self::NE  => self::NE,
        self::LT  => self::LT,
        self::LTE => self::LTE,
        self::GTE => self::GTE,
        self::GT  => self::GT,
    ];

    /**
     * Other conditional operators
     */
    public const BETWEEN     = 'BETWEEN';
    public const NOT_BETWEEN = 'NOT BETWEEN';
    public const EXISTS      = 'EXISTS';
    public const NOT_EXISTS  = 'NOT EXISTS';
    public const IN          = 'IN';
    public const NOT_IN      = 'NOT IN';
    public const LIKE        = 'LIKE';
    public const NOT_LIKE    = 'NOT LIKE';

    /**
     * REGEX operators
     */
    public const REGEX         = '~';
    public const REGEX_CS      = '~*';
    public const NOT_REGEX     = '!~';
    public const NOT_REGEX_CS  = '!~*';

    // valid operators excluding boolean operators
    public const OPERATORS = [
        self::EQ           => self::EQ,
        self::NEQ          => self::NEQ,
        self::NE           => self::NE,
        self::LT           => self::LT,
        self::LTE          => self::LTE,
        self::GTE          => self::GTE,
        self::GT           => self::GT,
        self::BETWEEN      => self::BETWEEN,
        self::NOT_BETWEEN  => self::NOT_BETWEEN,
        self::EXISTS       => self::EXISTS,
        self::NOT_EXISTS   => self::NOT_EXISTS,
        self::IN           => self::IN,
        self::NOT_IN       => self::NOT_IN,
        self::LIKE         => self::LIKE,
        self::NOT_LIKE     => self::NOT_LIKE,
        self::REGEX        => self::REGEX,
        self::REGEX_CS     => self::REGEX_CS,
        self::NOT_REGEX    => self::NOT_REGEX,
        self::NOT_REGEX_CS => self::NOT_REGEX_CS,
    ];

    /**
     * Boolean operators
     */
    public const AND = 'AND';
    public const OR  = 'OR';

    public const ASTERISK = '*';

    /**
     * Quantifiers
     */
    public const DISTINCT = 'DISTINCT';
    public const ALL      = 'ALL';
    public const SOME     = 'SOME';
    public const ANY      = 'ANY';

    public const QUANTIFIERS = [
        self::DISTINCT => self::DISTINCT,
        self::ALL      => self::ALL,
    ];

    /**
     * COMBINED-SETS
     */
    public const UNION     = 'UNION';
    public const INTERSECT = 'INTERSECT';

    public const SETS_COMBINATIONS = [
        self::UNION      => self::UNION,
        self::INTERSECT  => self::INTERSECT,
    ];

    public static function isValidJoin(string $join): bool
    {
        return isset(self::JOIN_TYPES[strtoupper($join)]);
    }

    public static function assertValidJoin(string $join)
    {
        if (!self::isValidJoin($join)) {
            throw new InvalidArgumentException(
                "Invalid or unsupported SQL JOIN type: '{$join}' provided!"
            );
        }
    }

    public static function isSupportedOperator(string $operator): bool
    {
        return isset(self::OPERATORS[strtoupper($operator)]);
    }

    public static function assertValidOperator($operator)
    {
        if (!is_string($operator)
            || !self::isSupportedOperator($operator)
        ) {
            throw new InvalidArgumentException(sprintf(
                "Invalid or unsupported SQL operator, '%s' provided!",
                is_string($operator) ? $operator : gettype($operator)
            ));
        }
    }

    public static function isEmptySQL($sql): bool
    {
        return !is_string($sql) || '' === trim($sql);
    }

        /**
     * Create and return a new Select command
     *
     * @param array|string|string[]|Literal2|Literal2[]|Select|Select[] $columns
     *      An array of columns with optional key-as-alias or a single column or
     *      the sql-asterisk
     * @param string!Select|null $from The db-table name or a sub-select statement
     * @param string|null $alias The db-table alias
     * @return Select
     */
    public static function select($columns = Sql::ASTERISK, $from = null, string $alias = null): Select
    {
        return new Select($columns, $from, $alias);
    }

    /**
     * Create a new Insert db-command and either return it or execute it trying
     * to create a new row or multiple new rows
     *
     * @param string|null $table
     * @return Insert
     */
    public function insert(string $table = null): Insert
    {
        return new Insert($table);
    }

    /**
     * Create a new Update db-command and either return or execute it
     *
     * @param string|null $table
     * @param array|null $data
     * @param string|array|Predicate|Where $where
     * @return Update
     */
    public static function update(string $table = null, array $data = null, $where = null): Update
    {
        return new Update($table);

        $num_args = func_num_args();
        if ($num_args < 2 || !isset($data)) {
            return $update;
        }
        if ($num_args === 2 || !isset($where)) {
            return $update->set($data);
        }

        return $update->set($data)->where($where);
    }

    /**
     * Create a new Delete db-command and either return or execute it
     *
     * @param string|null $table The db-table to delete from
     * @param string|array|Predicate|Where $where
     * @return Delete|bool|int
     */
    public static function delete($table = null, $where = null)
    {
        $delete = new Delete($table);
        if (func_num_args() < 2 || !isset($where)) {
            return $delete;
        }

        return $delete->where($where);
    }
}
