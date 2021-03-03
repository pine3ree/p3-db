<?php

/**
 * @package     p3-db
 * @subpackage  p3-db-test
 * @author      pine3ree https://github.com/pine3ree
 */

namespace P3\DbTest\Sql\Statement;

use InvalidArgumentException;
//use PDO;
use PHPUnit\Framework\TestCase;
use P3\Db\Sql\Driver;
use P3\Db\Sql;
use P3\Db\Sql\Alias;
use P3\Db\Sql\Expression;
use P3\Db\Sql\Identifier;
use P3\Db\Sql\Literal;
use P3\Db\Sql\Statement\Select;
use RuntimeException;

use function getenv;

class SelectTest extends TestCase
{
    /** @var Driver\MySql */
    private $driver;

    public function setUp(): void
    {
        $this->driver = new Driver\MySql();
    }

    public function tearDown()
    {
        $this->driver = null;
    }

    /**
     * @dataProvider provideColumns
     */
    public function testSelectColumns($columns, $expected_columns_sql)
    {
        $select = new Select();
        $select->columns($columns);
        $select->from('customer');

        self::assertEquals("SELECT {$expected_columns_sql} FROM `customer`", $select->getSQL($this->driver));
    }

    public function provideColumns(): array
    {
        return [
            [null, "*"],
            [[], "*"],
            ['*', "*"],
            ['', "*"],
            [
                new Literal("COUNT(*)"),
                "COUNT(*)",
            ],
            [
                new Expression("CONCAT('ABC', {str})", ['str' => 'DEF']),
                "CONCAT('ABC', :expr1)",
            ],
            [
                [
                    new Literal("COUNT(*)"),
                ],
                "COUNT(*)",
            ],
            [
                [
                    'nr' => new Literal("COUNT(*)"),
                ],
                "COUNT(*) AS `nr`",
            ],
            [
                [
                'first_name'
                ],
                '`first_name`',
            ],
            [
                [
                'firstName' => 'first_name'
                ],
                '`first_name` AS `firstName`',
            ],
        ];
    }

    public function testSelectColumnsWithTableAliasAddsPrefixIfMissing()
    {
        $select = new Select();
        $select->columns([]);
        $select->from('customer', 'c');

        self::assertEquals("SELECT `c`.* FROM `customer` `c`", $select->getSQL($this->driver));

        $select = new Select();
        $select->columns([
            'id',
            'c.name'
        ]);
        $select->from('customer', 'c');

        self::assertEquals("SELECT `c`.`id`, `c`.`name` FROM `customer` `c`", $select->getSQL($this->driver));
    }

    /**
     * @dataProvider provideColumn
     */
    public function testSelectColumn($column, ?string $alias, string $column_sql)
    {
        $select = new Select(null, 'product');
        $select->column($column, $alias);

        self::assertEquals("SELECT {$column_sql} FROM `product`", $select->getSQL($this->driver));
    }

    public function provideColumn(): array
    {
        return [
            ['unit_price', null, "`unit_price`"],
            ['unit_price', 'unitPrice', "`unit_price` AS `unitPrice`"],
            ['p.unit_price', 'unitPrice', "`p`.`unit_price` AS `unitPrice`"],
            ['`p`.`unit_price`', 'unitPrice', "`p`.`unit_price` AS `unitPrice`"],
            [new Literal("SUM(unit_price)"), 'totalPrice', "SUM(unit_price) AS `totalPrice`"],
            [
                new Expression("SUM(unit_price) + {tax}", ['tax' => 5.00]),
                'totalPrice',
                "SUM(unit_price) + :expr2 AS `totalPrice`",
            ],
        ];
    }

    public function textExceptionsOnInvalidColumnTypes()
    {
        $select = new Select(null, 'user');

        $this->expectException(InvalidArgumentException::class);
        $select->columns([new \stdClass()]);
        $this->expectException(InvalidArgumentException::class);
        $select->column(new \stdClass());

        $this->expectException(InvalidArgumentException::class);
        $select->columns([null]);
        $this->expectException(InvalidArgumentException::class);
        $select->column(null);

        $this->expectException(InvalidArgumentException::class);
        $select->columns([0]);
        $this->expectException(InvalidArgumentException::class);
        $select->column(0);
    }

    public function testSelectWithoutFromRisesExceptionOnGetSQL()
    {
        $select = new Select();
        $select->columns([]);

        $this->expectException(RuntimeException::class);
        self::assertEquals("SELECT *", $select->getSQL($this->driver));
    }

    public function testInvalidFromRisesException()
    {
        $this->expectException(InvalidArgumentException::class);
        (new Select())->from('', null);

        $this->expectException(InvalidArgumentException::class);
        (new Select())->from(new stdClass(), null);
    }

    public function testFromSubselectWithEmptyAliasRisesException()
    {
        $this->expectException(InvalidArgumentException::class);
        (new Select())->from(new Select([], 'subtable'), null);

        $this->expectException(InvalidArgumentException::class);
        (new Select())->from(new Select([], 'subtable', ''), null);
    }

    public function testSelectFromTable()
    {
        ($select = new Select())->from('product', null);
        self::assertEquals("SELECT * FROM `product`", $select->getSQL($this->driver));

        ($select = new Select())->from('product', 'p');
        self::assertEquals("SELECT `p`.* FROM `product` `p`", $select->getSQL($this->driver));
    }

    public function testSelectFromSubselect()
    {
        ($subSelect = new Select())->from('cart');
        ($select = new Select())->from($subSelect, 'c');
        self::assertEquals("SELECT `c`.* FROM (SELECT * FROM `cart`) `c`", $select->getSQL($this->driver));

        ($subSelect = new Select())->from('cart_product', 'cp')->where->gt('cp.price', 0);
        ($select = new Select())->from($subSelect, 'p');
        self::assertStringStartsWith(
            "SELECT `p`.* FROM (SELECT `cp`.* FROM `cart_product` `cp` WHERE `cp`.`price` > :gt",
            $select->getSQL($this->driver)
        );
    }

    public function testSelectWithLimit()
    {
        ($select = new Select())->from('user')->limit(10);
        self::assertEquals(
            "SELECT * FROM `user` LIMIT :limit1",
            $select->getSQL($this->driver)
        );
        self::assertEquals(10, $select->getParams()[':limit1'] ?? null);
    }

    public function testSelectWithNegativeLimit()
    {
        ($select = new Select())->from('user')->limit(-1);
        self::assertEquals(
            "SELECT * FROM `user` LIMIT :limit2",
            $select->getSQL($this->driver)
        );
        self::assertEquals(0, $select->getParams()[':limit2'] ?? null);
    }

    public function testSelectWithOffset()
    {
        ($select = new Select())->from('user')->offset(100);
        self::assertEquals(
            "SELECT * FROM `user` LIMIT " . PHP_INT_MAX . " OFFSET :offset3",
            $select->getSQL($this->driver)
        );
        self::assertEquals(100, $select->getParams()[':offset3'] ?? null);
    }

    public function testSelectZeroOrNegativeOffsetIsDiscarded()
    {
        ($select = new Select())->from('user')->offset(0);
        self::assertEquals(
            "SELECT * FROM `user`",
            $select->getSQL($this->driver)
        );

        ($select = new Select())->from('user')->offset(-1);
        self::assertEquals(
            "SELECT * FROM `user`",
            $select->getSQL($this->driver)
        );
    }

    public function testSelectWithLimitAndOffset()
    {
        ($select = new Select())->from('user')->limit(10)->offset(100);
        self::assertEquals(
            "SELECT * FROM `user` LIMIT :limit4 OFFSET :offset5",
            $select->getSQL($this->driver)
        );
        self::assertEquals(10, $select->getParams()[':limit4'] ?? null);
        self::assertEquals(100, $select->getParams()[':offset5'] ?? null);
    }

    public function testAnsiDriverDoesNotSupportLimitAndOffset()
    {
        ($select = new Select())->from('user')->limit(10)->offset(100);
        self::assertEquals(
            'SELECT * FROM "user" [LIMIT 10 OFFSET 100]',
            $select->getSQL(Driver::ansi())
        );
        self::assertEquals(null, $select->getParams()[':limit4'] ?? null);
        self::assertEquals(null, $select->getParams()[':offset5'] ?? null);
    }

    /**
     * @dataProvider provideGroupBy
     */
    public function testGroupByClause($groupBy, string $expectedSQL)
    {
        $select = (new Select())->sum("unit_price*quantity", "productTotal")->from('cart_product');

        $select->groupBy($groupBy);
        self::assertEquals(
            "SELECT SUM(unit_price*quantity) AS `productTotal` FROM `cart_product` GROUP BY {$expectedSQL}",
            $select->getSQL($this->driver)
        );

        // test replace
        $select->groupBy(['tax_id'], true);
        self::assertEquals(
            "SELECT SUM(unit_price*quantity) AS `productTotal` FROM `cart_product` GROUP BY `tax_id`",
            $select->getSQL($this->driver)
        );
    }

    public function provideGroupBy(): array
    {
        return [
            ["cart_id", "`cart_id`"],
            ["cart_id", "`cart_id`"],
            ["cart_id", "`cart_id`"],
            ["cart_id", "`cart_id`"],
            ["cart_id", "`cart_id`"],
            ["cart.id", "`cart`.`id`"],
            [new Alias("some.alias"), "`some.alias`"],
            [new Identifier("some.column"), "`some`.`column`"],
            [new Literal("(unit_price * quantity)"), "(unit_price * quantity)"],
        ];
    }

    /**
     * @dataProvider provideOrderBy
     */
    public function testOrderByClause($orderBy, $sortDirOrReplace, string $expectedSQL)
    {
        $select = (new Select())->from('product');

        $select->orderBy($orderBy, $sortDirOrReplace);
        self::assertEquals(
            "SELECT * FROM `product` ORDER BY {$expectedSQL}",
            $select->getSQL($this->driver)
        );

        // test replace
        $select->orderBy(['unit_price' => 'DESC'], true);
        self::assertEquals(
            "SELECT * FROM `product` ORDER BY `unit_price` DESC",
            $select->getSQL($this->driver)
        );
    }

    public function provideOrderBy(): array
    {
        return [
            ["id", null, "`id` ASC"],
            ["id", 'ASC', "`id` ASC"],
            ["id", 'asc', "`id` ASC"],
            ["id", 'DESC', "`id` DESC"],
            ["id", 'desc', "`id` DESC"],
            ["u.id", null, "`u`.`id` ASC"],
            [new Alias("some.alias"), null, "`some.alias` ASC"],
            [new Identifier("some.column"), null, "`some`.`column` ASC"],
            [new Literal("(unit_price * quantity)"), null, "(unit_price * quantity) ASC"],
        ];
    }
}