<?php

/**
 * @package     p3-db
 * @subpackage  p3-db-test
 * @author      pine3ree https://github.com/pine3ree
 */

namespace P3\DbTest\Sql\Predicate;

use InvalidArgumentException;
use P3\Db\Sql;
use P3\Db\Sql\Predicate;
use PHPUnit\Framework\TestCase;

class IsTest extends TestCase
{
    public function setUp(): void
    {
    }

    public function tearDown()
    {
    }

    public function testIsNull()
    {
        $predicate = new Predicate\Is('id', null);
        self::assertEquals('"id" IS NULL', $predicate->getSQL());
    }

    public function testIsNullWithString()
    {
        $predicate = new Predicate\Is('id', 'NULL');
        self::assertEquals('"id" IS NULL', $predicate->getSQL());
    }

    public function testIsNullWithStringIsCaseInsensitive()
    {
        $predicate = new Predicate\Is('id', 'null');
        self::assertEquals('"id" IS NULL', $predicate->getSQL());
    }

    public function testIsTrue()
    {
        $predicate = new Predicate\Is('id', true);
        self::assertEquals('"id" IS TRUE', $predicate->getSQL());
    }

    public function testIsTrueWithString()
    {
        $predicate = new Predicate\Is('id', 'TRUE');
        self::assertEquals('"id" IS TRUE', $predicate->getSQL());
    }

    public function testIsTrueWithStringIsCaseInsensitive()
    {
        $predicate = new Predicate\Is('id', 'true');
        self::assertEquals('"id" IS TRUE', $predicate->getSQL());
    }

    public function testIsFalse()
    {
        $predicate = new Predicate\Is('id', false);
        self::assertEquals('"id" IS FALSE', $predicate->getSQL());
    }

    public function testIsFalseWithString()
    {
        $predicate = new Predicate\Is('id', 'FALSE');
        self::assertEquals('"id" IS FALSE', $predicate->getSQL());
    }

    public function testIsFalseWithStringIsCaseInsensitive()
    {
        $predicate = new Predicate\Is('id', 'false');
        self::assertEquals('"id" IS FALSE', $predicate->getSQL());
    }

    public function testIsUnknown()
    {
        $predicate = new Predicate\Is('id', 'UNKNOWN');
        self::assertEquals('"id" IS UNKNOWN', $predicate->getSQL());
    }

    public function testIsUnknownIsCaseInsensitive()
    {
        $predicate = new Predicate\Is('id', 'unKnowN');
        self::assertEquals('"id" IS UNKNOWN', $predicate->getSQL());
    }

    public function testInvalidValueRaiseException()
    {
        $this->expectException(InvalidArgumentException::class);
        $predicate = new Predicate\Is('id', 'invalid-value');
    }

    public function testWhenIdentifierIsLiteral()
    {
        $predicate = new Predicate\Is(Sql::literal('(1 = 1) AS OneEqualsOne'), null);
        self::assertEquals('(1 = 1) AS OneEqualsOne IS NULL', $predicate->getSQL());
    }

    public function testWhenIdentifierIsAlias()
    {
        $predicate = new Predicate\Is(Sql::alias('customer.isEnabled'), true);
        self::assertEquals('"customer.isEnabled" IS TRUE', $predicate->getSQL());

        $predicate = new Predicate\Is(Sql::alias('some.alias'), null);
        self::assertEquals('"some.alias" IS NULL', $predicate->getSQL());
    }

    public function testWhenIdentifierHasTableOrAliasPrefix()
    {
        $predicate = new Predicate\Is('u.id', null);
        self::assertEquals('"u"."id" IS NULL', $predicate->getSQL());

        $predicate = new Predicate\Is('user.id', null);
        self::assertEquals('"user"."id" IS NULL', $predicate->getSQL());
    }

    public function testIsNot()
    {
        $predicate = new Predicate\IsNot('some_column', null);
        self::assertEquals('"some_column" IS NOT NULL', $predicate->getSQL());

        $predicate = new Predicate\IsNot('some_column', 'null');
        self::assertEquals('"some_column" IS NOT NULL', $predicate->getSQL());

        $predicate = new Predicate\IsNot('some_column', true);
        self::assertEquals('"some_column" IS NOT TRUE', $predicate->getSQL());

        $predicate = new Predicate\IsNot('some_column', 'true');
        self::assertEquals('"some_column" IS NOT TRUE', $predicate->getSQL());

        $predicate = new Predicate\IsNot('some_column', false);
        self::assertEquals('"some_column" IS NOT FALSE', $predicate->getSQL());

        $predicate = new Predicate\IsNot('some_column', 'false');
        self::assertEquals('"some_column" IS NOT FALSE', $predicate->getSQL());

        $predicate = new Predicate\IsNot('some_column', 'unknown');
        self::assertEquals('"some_column" IS NOT UNKNOWN', $predicate->getSQL());
    }

    public function testIsFalsePredicate()
    {
        $predicate = new Predicate\IsFalse('some_column');
        self::assertEquals('"some_column" IS FALSE', $predicate->getSQL());
    }

    public function testIsNotNullPredicate()
    {
        $predicate = new Predicate\IsNotNull('some_column');
        self::assertEquals('"some_column" IS NOT NULL', $predicate->getSQL());
    }

    public function testIsNotUnknownPredicate()
    {
        $predicate = new Predicate\IsNotUnknown('some_column');
        self::assertEquals('"some_column" IS NOT UNKNOWN', $predicate->getSQL());
    }

    public function testIsTruePredicate()
    {
        $predicate = new Predicate\IsTrue('some_column');
        self::assertEquals('"some_column" IS TRUE', $predicate->getSQL());
    }

    public function testIsUnknownPredicate()
    {
        $predicate = new Predicate\IsUnknown('some_column');
        self::assertEquals('"some_column" IS UNKNOWN', $predicate->getSQL());
    }
}