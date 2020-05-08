<?php

/**
 * @package p3-db
 * @author  pine3ree https://github.com/pine3ree
 */

namespace P3\Db\Sql;

use InvalidArgumentException;
use P3\Db\Sql\Element;
use P3\Db\Sql\Statement\Select;

/**
 * Predicate represents a single SQL condition that can be evaluates by the underlying
 * database software. It is usually part of set of conditions (PredicateSet) such
 * as the WHERE, the HAVING and the ON clauses or their nested sets of conditions.
 */
abstract class Predicate extends Element
{
    protected static function assertValidIdentifier($identifier)
    {
        if (!is_string($identifier) && ! $identifier instanceof Literal) {
            throw new InvalidArgumentException(sprintf(
                "A predicate identifier must be either a string or an SQL Literal,"
                . " '%s' provided in class `%s`!!",
                is_object($identifier) ? get_class($identifier) : gettype($identifier),
                static::class
            ));
        }
    }

    protected static function assertValidValue($value)
    {
        $is_valid = is_scalar($value) || null === value || $value instanceof Literal;
        if (!$is_valid) {
            throw new InvalidArgumentException(sprintf(
                "A predicte value must be either a scalar or an Sql Literal"
                . " expression instance, `%s` provided in class``%s!",
                is_object($value) ? get_class($value) : gettype($value),
                static::class
            ));
        }
    }

    public static function newLiteral(string $literal): Predicate\Literal
    {
        return new Predicate\Literal($literal);
    }

    public static function newExpression(string $expression, array $params = []): Predicate\Expression
    {
        return new Predicate\Expression($expression, $params);
    }

    public static function newBetween($identifier, array $limits): Predicate\Between
    {
        return new Predicate\Between($identifier, $limits);
    }

    public static function newNotBetween($identifier, array $limits): Predicate\NotBetween
    {
        return new Predicate\NotBetween($identifier, $limits);
    }

    public static function newExists(Select $select): Predicate\Exists
    {
        return new Predicate\Exists($select);
    }

    public static function newNotExists(Select $select): Predicate\NotExists
    {
        return new Predicate\NotExists($select);
    }

    public static function newIn($identifier, array $value_list): Predicate\In
    {
        return new Predicate\In($identifier, $value_list);
    }

    public static function newNotIn($identifier, array $value_list): Predicate\NotIn
    {
        return new Predicate\NotIn($identifier, $value_list);
    }

    public static function newLike($identifier, array $value): Predicate\like
    {
        return new Predicate\Like($identifier, $value);
    }

    public static function newNotLike($identifier, array $value): Predicate\notLike
    {
        return new Predicate\NotLike($identifier, $value);
    }

    public static function newEqual($identifier, array $value): Predicate\Comparison
    {
        return new Predicate\Comparison($identifier, Sql::EQUAL, $value);
    }

    public static function newNotEqual($identifier, array $value): Predicate\Comparison
    {
        return new Predicate\Comparison($identifier, Sql::NOT_EQUAL, $value);
    }

    public static function newLessThan($identifier, array $value): Predicate\Comparison
    {
        return new Predicate\Comparison($identifier, Sql::LESS_THAN, $value);
    }

    public static function newLessThanEqual($identifier, array $value): Predicate\Comparison
    {
        return new Predicate\Comparison($identifier, Sql::LESS_THAN_EQUAL, $value);
    }

    public static function newGreaterThanEqual($identifier, array $value): Predicate\Comparison
    {
        return new Predicate\Comparison($identifier, Sql::GREATER_THAN_EQUAL, $value);
    }

    public static function newGreaterThan($identifier, array $value): Predicate\Comparison
    {
        return new Predicate\Comparison($identifier, Sql::GREATER_THAN, $value);
    }
}
