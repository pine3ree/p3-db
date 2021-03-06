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
use P3\Db\Sql\Predicate\Set;
use P3\Db\Sql\Statement\Select;
use P3\DbTest\DiscloseTrait;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

class SetTest extends TestCase
{
    use DiscloseTrait;

    public function setUp(): void
    {
    }

    public function tearDown()
    {
    }

    private function buildPredicateFromSpecs($specs, Set &$predicateSet = null)
    {
        $predicateSet = $predicateSet ?? new Predicate\Set();
        return $this->invokeMethod($predicateSet, 'buildPredicateFromSpecs', $specs);
    }

    private function buildPredicate($specs, Set &$predicateSet = null, ...$args)
    {
        $predicateSet = $predicateSet ?? new Predicate\Set();
        return $this->invokeMethod($predicateSet, 'buildPredicate', $specs, ...$args);
    }

    public function testContructorWithoutPredicatesAndWithoutLogicalOp()
    {
        $predicateSet = new Predicate\Set();
        self::assertSame([], $predicateSet->getPredicates());
        self::assertTrue($predicateSet->isEmpty());
        self::assertSame("", $predicateSet->getSQL());
        self::assertSame("AND", $predicateSet->getDefaultLogicalOperator());
        self::assertSame(null, $predicateSet->getNextLogicalOperator());
        self::assertSame([], $predicateSet->getParams());
        self::assertSame([], $predicateSet->getParamsTypes());
    }

    public function testContructorWithoutPredicatesButOrLogicalOp()
    {
        $predicateSet = new Predicate\Set(null, Set::COMB_OR);
        self::assertSame("OR", $predicateSet->getDefaultLogicalOperator());

        $predicateSet = new Predicate\Set(null, Sql::OR);
        self::assertSame("OR", $predicateSet->getDefaultLogicalOperator());
    }

    public function testContructorWithEmptyStringPredicates()
    {
        $predicateSet = new Predicate\Set('');
        self::assertSame([], $predicateSet->getPredicates());
    }

    public function testContructorWithEmptyArrayPredicates()
    {
        $predicateSet = new Predicate\Set([]);
        self::assertSame([], $predicateSet->getPredicates());
    }

    public function testContructorWithPredicateSet()
    {
        $set = new Predicate\Set(['id' => 42]);
        $predicateSet = new Predicate\Set($set);
        self::assertSame($set->getPredicates(), $predicateSet->getPredicates());
        self::assertStringMatchesFormat('"id" = :eq%d', $sql = $predicateSet->getSQL());
        self::assertSame($sql, $predicateSet->getSQL());
    }

    public function testContructorWithArrayPredicates()
    {
        $predicateSet = new Predicate\Set(['id' => 42]);
        self::assertStringMatchesFormat('"id" = :eq%d', $sql = $predicateSet->getSQL());
        self::assertSame($sql, $predicateSet->getSQL());
    }

    public function testContructorWithArrayValuePredicates()
    {
        $predicateSet = new Predicate\Set(['id' => [1, 2]]);
        self::assertStringMatchesFormat('"id" IN (:in%d, :in%d)', $predicateSet->getSQL());
    }

    public function testContructorWithArrayWithNumericIndexPredicates()
    {
        $predicateSet = new Predicate\Set([
            "id > 42",
        ]);
        self::assertStringMatchesFormat('id > 42', $predicateSet->getSQL());
    }

    public function testMagicProperties()
    {
        $predicateSet = new Predicate\Set([
            "id > 42",
        ]);

        self::assertSame($predicateSet->getPredicates(), $predicateSet->predicates);
        self::assertSame($predicateSet->getDefaultLogicalOperator(), $predicateSet->defaultLogicalOperator);
        self::assertSame($predicateSet->getNextLogicalOperator(), $predicateSet->nextLogicalOperator);
    }

    public function testBuildPredicateFromEmptySpecsWithRaisesException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->buildPredicateFromSpecs($specs = []);
    }

    public function testBuildPredicateFromSpecsWithOneNumericKeyToValueElementRaisesException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->buildPredicateFromSpecs($specs = ["FALSE"]);
    }

    public function testBuildPredicateFromSpecsWithOneKeyToValueElement()
    {
        $specs = ['id' => 42];
        $predicate = $this->buildPredicateFromSpecs($specs);
        self::assertStringMatchesFormat('"id" = :eq%d', $predicate->getSQL());
    }

    public function testBuildPredicateFromSpecsWithOneKeyToArrayValueElement()
    {
        $specs = ['id' => [1, 2]];
        $predicate = $this->buildPredicateFromSpecs($specs);
        self::assertStringMatchesFormat('"id" IN (:in%d, :in%d)', $predicate->getSQL());
    }

    public function testBuildPredicateFromSpecsWithLogicalOperatorKeyToArrayValueElement()
    {
        $specs = ['||' => [
            'enabled IS TRUE',
            'published IS TRUE',
        ]];

        $predicate = $this->buildPredicateFromSpecs($specs);

        self::assertInstanceOf(Predicate\Set::class, $predicate);
        self::assertSame('enabled IS TRUE OR published IS TRUE', $predicate->getSQL());
    }

    public function testBuildPredicateFromSpecsWithLogicalOperatorKeyToNotArrayValueElementRisesException()
    {
        $specs = ['&&' => "MUST-BE-AN-ARRAY"];

        $this->expectException(InvalidArgumentException::class);
        $predicate = $this->buildPredicateFromSpecs($specs);
    }

    public function testBuildPredicateFromSpecsWithTwoElementsWithValidLogicalOpAnsSubSpecsArray()
    {
        $specs = ['||', ['id', '=', 42]];

        $predicate = $this->buildPredicateFromSpecs($specs, $predicateSet);
        self::assertStringMatchesFormat('"id" = :eq%d', $predicate->getSQL());
        self::assertSame(Sql::OR, $predicateSet->getNextLogicalOperator());
    }

    public function testBuildPredicateFromSpecsWithTwoElementsWithValidLogicalOpAnsSubSpecsString()
    {
        $specs = ['||', 'id = 42'];

        $predicate = $this->buildPredicateFromSpecs($specs, $predicateSet);
        self::assertStringMatchesFormat('id = 42', $predicate->getSQL());
        self::assertSame(Sql::OR, $predicateSet->getNextLogicalOperator());
    }

    public function testBuildPredicateFromSpecsWithTwoElementsWithValidLogicalOpAnsSubPredicate()
    {
        $specs = ['||', new Predicate\Literal('id > 42')];

        $predicate = $this->buildPredicateFromSpecs($specs, $predicateSet);
        self::assertStringMatchesFormat('id > 42', $predicate->getSQL());
        self::assertSame(Sql::OR, $predicateSet->getNextLogicalOperator());
    }

    public function testBuildPredicateFromSpecsWithTwoElementsWithInvalidLogicalOpRisesException()
    {
        $specs = ['?', ['id', '=', 42]];

        $this->expectException(InvalidArgumentException::class);
        $predicate = $this->buildPredicateFromSpecs($specs);
    }

    /**
     * @dataProvider provideInvalidSpecs
     */
    public function testBuildPredicateFromSpecsWithTwoElementsWithInvalidSubSpecs($subSpecs)
    {
        $specs = ['&&', $subSpecs];

        $this->expectException(InvalidArgumentException::class);
        $predicate = $this->buildPredicateFromSpecs($specs);
    }

    public function testBuildPredicateFromSpecsWithThreeElements()
    {
        $specs = ['id', '>', 42];

        $predicate = $this->buildPredicateFromSpecs($specs);
        self::assertStringMatchesFormat('"id" > :gt%d', $predicate->getSQL());
    }

    public function testBuildPredicateFromThreeFoldSpecsAndComparisonOpOrAliasAndArrayValue()
    {
        $specs = ['id', '=', [1, 2]];
        $predicate = $this->buildPredicateFromSpecs($specs);
        self::assertStringMatchesFormat('"id" IN (:in%d, :in%d)', $predicate->getSQL());

        $specs = ['id', 'in', [3, 4]];
        $predicate = $this->buildPredicateFromSpecs($specs);
        self::assertStringMatchesFormat('"id" IN (:in%d, :in%d)', $predicate->getSQL());

        $specs = ['id', '!=', [5, 6]];
        $predicate = $this->buildPredicateFromSpecs($specs);
        self::assertStringMatchesFormat('"id" NOT IN (:in%d, :in%d)', $predicate->getSQL());

        $specs = ['id', '<>', [7, 8]];
        $predicate = $this->buildPredicateFromSpecs($specs);
        self::assertStringMatchesFormat('"id" NOT IN (:in%d, :in%d)', $predicate->getSQL());

        $specs = ['id', 'notIn', [9, 0]];
        $predicate = $this->buildPredicateFromSpecs($specs);
        self::assertStringMatchesFormat('"id" NOT IN (:in%d, :in%d)', $predicate->getSQL());

        $specs = ['id', '>', ['a', 'b']];
        $this->expectException(InvalidArgumentException::class);
        $predicate = $this->buildPredicateFromSpecs($specs);
    }

    public function testBuildPredicateFromSpecsAndOtherOperatorsAndAliases()
    {
        $specs = ['id', Sql::BETWEEN, 24, 42];
        $predicate = $this->buildPredicateFromSpecs($specs);
        self::assertStringMatchesFormat('"id" BETWEEN :min%d AND :max%d', $predicate->getSQL());

        $specs = ['id', 'between', 24, 42];
        $predicate = $this->buildPredicateFromSpecs($specs);
        self::assertStringMatchesFormat('"id" BETWEEN :min%d AND :max%d', $predicate->getSQL());

        $specs = ['id', Sql::NOT_BETWEEN, 24, 42];
        $predicate = $this->buildPredicateFromSpecs($specs);
        self::assertStringMatchesFormat('"id" NOT BETWEEN :min%d AND :max%d', $predicate->getSQL());

        $specs = ['id', 'notBetween', 24, 42];
        $predicate = $this->buildPredicateFromSpecs($specs);
        self::assertStringMatchesFormat('"id" NOT BETWEEN :min%d AND :max%d', $predicate->getSQL());

        $specs = ['published', Sql::IS, true];
        $predicate = $this->buildPredicateFromSpecs($specs);
        self::assertStringMatchesFormat('"published" IS TRUE', $predicate->getSQL());

        $specs = ['published', Sql::IS_NOT, false];
        $predicate = $this->buildPredicateFromSpecs($specs);
        self::assertStringMatchesFormat('"published" IS NOT FALSE', $predicate->getSQL());

        $specs = ['published', 'isNot', null];
        $predicate = $this->buildPredicateFromSpecs($specs);
        self::assertStringMatchesFormat('"published" IS NOT NULL', $predicate->getSQL());

        $specs = ['name', Sql::LIKE, 'A%'];
        $predicate = $this->buildPredicateFromSpecs($specs);
        self::assertStringMatchesFormat('"name" LIKE :like%d', $predicate->getSQL());

        $specs = ['name', Sql::NOT_LIKE, 'A%'];
        $predicate = $this->buildPredicateFromSpecs($specs);
        self::assertStringMatchesFormat('"name" NOT LIKE :like%d', $predicate->getSQL());

        $specs = ['name', 'notLike', 'A%'];
        $predicate = $this->buildPredicateFromSpecs($specs);
        self::assertStringMatchesFormat('"name" NOT LIKE :like%d', $predicate->getSQL());
    }

    public function testBuildPredicateFromSpecsWithUnsupportedOperator()
    {
        $specs = ['id', 'UNSUPPORTED', 24, 42];
        $this->expectException(InvalidArgumentException::class);
        $predicate = $this->buildPredicateFromSpecs($specs);
//        self::assertStringMatchesFormat('"id" BETWEEN :min%d AND :max%d', $predicate->getSQL());
    }

    /**
     * @dataProvider provideInvalidPredicateTypes
     */
    public function testAddInvalidPredicateRaisesException($predicate)
    {
        $predicateSet = new Predicate\Set();

        $this->expectException(InvalidArgumentException::class);
        $predicateSet->addPredicate($predicate);
    }

    /**
     * @dataProvider provideInvalidSubSpecs
     */
    public function testAddPredicateWithInvalidSubSpecsRaisesException($specs)
    {
        $predicateSet = new Predicate\Set();

        $this->expectException(InvalidArgumentException::class);
        $predicateSet->addPredicate($specs);
    }

    /**
     * @dataProvider provideEmptySpecs
     */
    public function testAddPredicateWithEmptySpecsHasNoEffect($specs)
    {
        $predicateSet = new Predicate\Set();
        $predicateSet->addPredicate($specs);
        self::assertTrue($predicateSet->isEmpty());
    }

    /**
     * @dataProvider provideEmptySpecs
     */
    public function testBuildPredicateWithEmptyValueAndNoEmptyCheckAndNotThrowReturnsNull($specs)
    {
        self::assertNull($this->buildPredicate($specs, $set, false, false));
    }

    /**
     * @dataProvider provideEmptySpecs
     */
    public function testBuildPredicateWithEmptyValueAndEmptyCheckAndNotThrowReturnsNull($specs)
    {
        self::assertNull($this->buildPredicate($specs, $set, true, false));
    }

    /**
     * @dataProvider provideEmptySpecs
     */
    public function testBuildPredicateWithEmptyValueAndNoEmptyCheckAndThrowRaisesException($specs)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->buildPredicate($specs, $set, false, true);
    }

    /**
     * @dataProvider provideEmptySpecs
     */
    public function testBuildPredicateWithEmptyValueAndEmptyCheckAndThrowRaisesException($specs)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->buildPredicate($specs, $set, true, true);
    }

    /**
     * @dataProvider provideInvalidSpecs
     */
    public function testBuildPredicateWithInvalidValueAndEmptyCheckAndThrowRaisesException($specs)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->buildPredicate($specs, $set, true, true);
    }

    /**
     * @dataProvider provideInvalidSpecs
     */
    public function testBuildPredicateWithInvalidValueAndEmptyCheckAndNotThrowReturnsNull($specs)
    {
        self::assertNull($this->buildPredicate($specs, $set, true, false));
    }

    /**
     * @dataProvider provideInvalidSpecs
     */
    public function testBuildPredicateWithInvalidValueAndNoEmptyCheckAndThrowRaisesException($specs)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->buildPredicate($specs, $set, false, true);
    }

    /**
     * @dataProvider provideInvalidSpecs
     */
    public function testBuildPredicateWithInvalidValueAndNoEmptyCheckAndNotThrowReturnsNull($specs)
    {
        self::assertNull($this->buildPredicate($specs, $set, false, false));
    }

    public function testGetSql()
    {
        $predicateSet = new Predicate\Set();
        self::assertSame('', $predicateSet->getSQL());

        // only nested empty set results in empty sql
        $predicateSet->addPredicate(new Predicate\Set());
        self::assertSame('', $predicateSet->getSQL());
    }

//    public function testNotSetSql()
//    {
//        $predicateSet = new Predicate\NotSet('id', 24, 42);
//
//        self::assertStringMatchesFormat('"id" NOT BETWEEN :min%d AND :max%d', $predicateSet->getSQL());
//    }

    public function testThatCloningAlsoClonesPredicates()
    {
        $predicateSet = new Predicate\Set(['id' => 42]);
        $clonedSet = clone $predicateSet;

        $oPredicates = $predicateSet->getPredicates();
        $cPredicates = $clonedSet->getPredicates();

        foreach ($oPredicates as $i => $parr) {
            list($olop, $oPred) = $parr;
            list($clop, $cPred) = $cPredicates[$i];
            self::assertEquals($oPred, $cPred);
            self::assertNotSame($oPred, $cPred);
        }
    }

    public function provideInvalidSpecs(): array
    {
        return [
            [null],
            [123],
            [new \stdClass()],
        ];
    }

    public function provideInvalidSubSpecs(): array
    {
        return [
            [[null]],
            [[123, 4.56]],
            [[new \stdClass()]],
        ];
    }

    public function provideInvalidPredicateTypes(): array
    {
        return [
            [null],
            [123],
            [1.23],
            [new \stdClass()],
        ];
    }

    public function provideInvalidNonEmptyPredicateOrSpecs(): array
    {
        return [
            [[123]],
            [[1, 2, 3]],
        ];
    }

    public function provideEmptySpecs(): array
    {
        return [
            [''],
            [[]],
        ];
    }

    public function testProxyMethods()
    {
        $c = 0;
        $predicateSet = new Predicate\Set();

        $c += 1;
        $fluent = $predicateSet->literal("TRUE IS TRUE");
        self::assertSame($fluent, $predicateSet);
        self::assertSame('TRUE IS TRUE', $predicateSet->getSQL());
        self::assertCount($c, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\Literal::class, $predicateSet->getPredicates()[$c - 1][1]);

        $c += 1;
        $fluent = $predicateSet->expression("id >= {idmin}", ['idmin' => 42]);
        self::assertSame($fluent, $predicateSet);
        self::assertCount($c, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\Expression::class, $predicateSet->getPredicates()[$c - 1][1]);

        $c += 1;
        $fluent = $predicateSet->expr("id <= {idmax}", ['idmax' => 999]);
        self::assertSame($fluent, $predicateSet);
        self::assertCount($c, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\Expression::class, $predicateSet->getPredicates()[$c - 1][1]);

        $c += 1;
        $fluent = $predicateSet->all("id", '<', (new Select('id', 'product')));
        self::assertStringMatchesFormat('%s ALL(%s', $predicateSet->getSQL());
        self::assertSame($fluent, $predicateSet);
        self::assertCount($c, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\All::class, $predicateSet->getPredicates()[$c - 1][1]);

        $c += 1;
        $fluent = $predicateSet->any("id", '>', (new Select('id', 'product')));
        self::assertSame($fluent, $predicateSet);
        self::assertStringMatchesFormat('%s ANY(%s', $predicateSet->getSQL());
        self::assertCount($c, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\Any::class, $predicateSet->getPredicates()[$c - 1][1]);

        $c += 1;
        $fluent = $predicateSet->some("id", '=', (new Select('id', 'product')));
        self::assertSame($fluent, $predicateSet);
        self::assertStringMatchesFormat('%s SOME(%s', $predicateSet->getSQL());
        self::assertCount($c, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\Some::class, $predicateSet->getPredicates()[$c - 1][1]);

        $c += 1;
        $fluent = $predicateSet->between('id', 11, 22);
        self::assertSame($fluent, $predicateSet);
        self::assertStringMatchesFormat('%s BETWEEN %s', $predicateSet->getSQL());
        self::assertCount($c, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\Between::class, $predicateSet->getPredicates()[$c - 1][1]);

        $c += 1;
        $fluent = $predicateSet->notBetween('id', 11, 22);
        self::assertSame($fluent, $predicateSet);
        self::assertStringMatchesFormat('%s NOT BETWEEN %s', $predicateSet->getSQL());
        self::assertCount($c, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\Between::class, $predicateSet->getPredicates()[$c - 1][1]);

        $c += 1;
        $fluent = $predicateSet->exists(new Select('id', 'user'));
        self::assertSame($fluent, $predicateSet);
        self::assertStringMatchesFormat('%s EXISTS (%s', $predicateSet->getSQL());
        self::assertCount($c, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\Exists::class, $predicateSet->getPredicates()[$c - 1][1]);

        $c = 0;
        $predicateSet = new Predicate\Set();

        $c += 1;
        $fluent = $predicateSet->notExists(new Select('*id', 'user'));
        self::assertSame($fluent, $predicateSet);
        self::assertStringMatchesFormat('NOT EXISTS (%s', $predicateSet->getSQL());
        self::assertCount(1, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\NotExists::class, $predicateSet->getPredicates()[$c - 1][1]);

        $c += 1;
        $fluent = $predicateSet->in('id', [1, 2]);
        self::assertSame($fluent, $predicateSet);
        self::assertStringMatchesFormat('%s IN (%s', $predicateSet->getSQL());
        self::assertCount($c, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\In::class, $predicateSet->getPredicates()[$c - 1][1]);

        $c += 1;
        $fluent = $predicateSet->notIn('id', [1, 2]);
        self::assertSame($fluent, $predicateSet);
        self::assertStringMatchesFormat('%s NOT IN (%s', $predicateSet->getSQL());
        self::assertCount($c, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\NotIn::class, $predicateSet->getPredicates()[$c - 1][1]);

        $c += 1;
        $fluent = $predicateSet->in('id', [1, 2]);
        self::assertSame($fluent, $predicateSet);
        self::assertStringMatchesFormat('%s IN (%s', $predicateSet->getSQL());
        self::assertCount($c, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\In::class, $predicateSet->getPredicates()[$c - 1][1]);

        $c += 1;
        $fluent = $predicateSet->notIn('id', [1, 2]);
        self::assertSame($fluent, $predicateSet);
        self::assertStringMatchesFormat('%s NOT IN (%s', $predicateSet->getSQL());
        self::assertCount($c, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\NotIn::class, $predicateSet->getPredicates()[$c - 1][1]);

        $c += 1;
        $fluent = $predicateSet->is('enabled', true);
        self::assertSame($fluent, $predicateSet);
        self::assertStringEndsWith(' IS TRUE', $predicateSet->getSQL());
        self::assertCount($c, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\Is::class, $predicateSet->getPredicates()[$c - 1][1]);

        $c += 1;
        $fluent = $predicateSet->isNot('id', null);
        self::assertSame($fluent, $predicateSet);
        self::assertStringEndsWith(' IS NOT NULL', $predicateSet->getSQL());
        self::assertCount($c, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\IsNot::class, $predicateSet->getPredicates()[$c - 1][1]);

        $c += 1;
        $fluent = $predicateSet->isNull('id');
        self::assertSame($fluent, $predicateSet);
        self::assertStringEndsWith(' IS NULL', $predicateSet->getSQL());
        self::assertCount($c, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\IsNull::class, $predicateSet->getPredicates()[$c - 1][1]);

        $c += 1;
        $fluent = $predicateSet->isNotNull('id');
        self::assertSame($fluent, $predicateSet);
        self::assertStringEndsWith(' IS NOT NULL', $predicateSet->getSQL());
        self::assertCount($c, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\IsNotNull::class, $predicateSet->getPredicates()[$c - 1][1]);

        $c = 0;
        $predicateSet = new Predicate\Set();

        $c += 1;
        $fluent = $predicateSet->isTrue('enabled');
        self::assertSame($fluent, $predicateSet);
        self::assertStringEndsWith(' IS TRUE', $predicateSet->getSQL());
        self::assertCount($c, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\IsTrue::class, $predicateSet->getPredicates()[$c - 1][1]);

        $c += 1;
        $fluent = $predicateSet->isFalse('enabled');
        self::assertSame($fluent, $predicateSet);
        self::assertStringEndsWith(' IS FALSE', $predicateSet->getSQL());
        self::assertCount($c, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\IsFalse::class, $predicateSet->getPredicates()[$c - 1][1]);

        $c += 1;
        $fluent = $predicateSet->isUnknown('done');
        self::assertSame($fluent, $predicateSet);
        self::assertStringEndsWith(' IS UNKNOWN', $predicateSet->getSQL());
        self::assertCount($c, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\IsUnknown::class, $predicateSet->getPredicates()[$c - 1][1]);

        $c += 1;
        $fluent = $predicateSet->isNotUnknown('done');
        self::assertSame($fluent, $predicateSet);
        self::assertStringEndsWith(' IS NOT UNKNOWN', $predicateSet->getSQL());
        self::assertCount($c, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\IsNotUnknown::class, $predicateSet->getPredicates()[$c - 1][1]);

        $c += 1;
        $fluent = $predicateSet->like('name', 'A%');
        self::assertSame($fluent, $predicateSet);
        self::assertStringMatchesFormat('%s LIKE %s', $predicateSet->getSQL());
        self::assertCount($c, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\Like::class, $predicateSet->getPredicates()[$c - 1][1]);

        $c += 1;
        $fluent = $predicateSet->notLike('name', 'A%');
        self::assertSame($fluent, $predicateSet);
        self::assertStringMatchesFormat('%s NOT LIKE %s', $predicateSet->getSQL());
        self::assertCount($c, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\NotLike::class, $predicateSet->getPredicates()[$c - 1][1]);

        $c = 0;
        $predicateSet = new Predicate\Set();

        $c += 1;
        $fluent = $predicateSet->equal('id', 42);
        self::assertSame($fluent, $predicateSet);
        self::assertStringMatchesFormat('"id" = %s', $predicateSet->getSQL());
        self::assertCount($c, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\Comparison::class, $predicateSet->getPredicates()[$c - 1][1]);

        $c += 1;
        $fluent = $predicateSet->notEqual('id', 42);
        self::assertSame($fluent, $predicateSet);
        self::assertStringMatchesFormat('%s "id" != %s', $predicateSet->getSQL());
        self::assertCount($c, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\Comparison::class, $predicateSet->getPredicates()[$c - 1][1]);

        $c += 1;
        $fluent = $predicateSet->eq('type_id', 42);
        self::assertSame($fluent, $predicateSet);
        self::assertStringMatchesFormat('%s "type_id" = %s', $predicateSet->getSQL());
        self::assertCount($c, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\Comparison::class, $predicateSet->getPredicates()[$c - 1][1]);

        $c += 1;
        $fluent = $predicateSet->neq('type_id', 42);
        self::assertSame($fluent, $predicateSet);
        self::assertStringMatchesFormat('%s "type_id" != %s', $predicateSet->getSQL());
        self::assertCount($c, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\Comparison::class, $predicateSet->getPredicates()[$c - 1][1]);

        $c += 1;
        $fluent = $predicateSet->ne('type_id', 42);
        self::assertSame($fluent, $predicateSet);
        self::assertStringMatchesFormat('%s "type_id" <> %s', $predicateSet->getSQL());
        self::assertCount($c, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\Comparison::class, $predicateSet->getPredicates()[$c - 1][1]);

        $c = 0;
        $predicateSet = new Predicate\Set();

        $c += 1;
        $fluent = $predicateSet->lessThan('id', 42);
        self::assertSame($fluent, $predicateSet);
        self::assertStringMatchesFormat('"id" < :lt%d', $predicateSet->getSQL());
        self::assertCount($c, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\Comparison::class, $predicateSet->getPredicates()[$c - 1][1]);

        $c += 1;
        $fluent = $predicateSet->lt('other_id', 42);
        self::assertSame($fluent, $predicateSet);
        self::assertStringMatchesFormat('%s "other_id" < :lt%d', $predicateSet->getSQL());
        self::assertCount($c, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\Comparison::class, $predicateSet->getPredicates()[$c - 1][1]);

        $c += 1;
        $fluent = $predicateSet->lessThanEqual('id', 42);
        self::assertSame($fluent, $predicateSet);
        self::assertStringMatchesFormat('%s "id" <= :lte%d', $predicateSet->getSQL());
        self::assertCount($c, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\Comparison::class, $predicateSet->getPredicates()[$c - 1][1]);

        $c += 1;
        $fluent = $predicateSet->lte('other_id', 42);
        self::assertSame($fluent, $predicateSet);
        self::assertStringMatchesFormat('%s "other_id" <= :lte%d', $predicateSet->getSQL());
        self::assertCount($c, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\Comparison::class, $predicateSet->getPredicates()[$c - 1][1]);

        $c += 1;
        $fluent = $predicateSet->greaterThanEqual('id', 42);
        self::assertSame($fluent, $predicateSet);
        self::assertStringMatchesFormat('%s "id" >= :gte%d', $predicateSet->getSQL());
        self::assertCount($c, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\Comparison::class, $predicateSet->getPredicates()[$c - 1][1]);

        $c += 1;
        $fluent = $predicateSet->gte('other_id', 42);
        self::assertSame($fluent, $predicateSet);
        self::assertStringMatchesFormat('%s "other_id" >= :gte%d', $predicateSet->getSQL());
        self::assertCount($c, $predicateSet->getPredicates());
        self::assertInstanceOf(Predicate\Comparison::class, $predicateSet->getPredicates()[$c - 1][1]);
    }

    public function testAndOrCalls()
    {
        $predicateSet = new Predicate\Set();

        $predicateSet->or();
        self::assertSame(Sql::OR, $predicateSet->getNextLogicalOperator());

        $predicateSet->and();
        self::assertSame(Sql::AND, $predicateSet->getNextLogicalOperator());
    }


    public function testOpenWithoutLogicalOperator()
    {
        $predicateSet = new Predicate\Set(['id' => 42]);

        $nestedSet = $predicateSet->open();

        self::assertInstanceOf(Set::class, $nestedSet);
        self::assertSame($nestedSet->getParent(), $nestedSet->parent);
        self::assertSame($predicateSet, $nestedSet->parent);
        self::assertSame([], $nestedSet->getPredicates());
        self::assertSame(Sql::AND, $nestedSet->getDefaultLogicalOperator());
    }

    public function testOpenWithLogicalOperator()
    {
        $predicateSet = new Predicate\Set(['id' => 42]);

        $nestedSet = $predicateSet->open(Sql::OR);
        self::assertSame(Sql::OR, $nestedSet->getDefaultLogicalOperator());
    }

    public function testCloseNestedSet()
    {
        $predicateSet = new Predicate\Set(['id' => 42]);
        $nestedSet = $predicateSet->open();
        $unnest = $nestedSet->close();

        self::assertTrue($nestedSet->isEmpty());
        self::assertSame($nestedSet->getParent(), $nestedSet->parent);
    }

    public function testCloseUnnestedSetRaisesException()
    {
        $predicateSet = new Predicate\Set();
        $nestedSet = $predicateSet->open();

        $this->expectException(RuntimeException::class);
        $nestedSet->close()->close();
    }

    public function testMagicGetter()
    {
        $predicateSet = new Predicate\Set(['id' => 42]);

        self::assertSame($predicateSet->getPredicates(), $predicateSet->predicates);

        $this->expectException(\RuntimeException::class);
        $predicateSet->nonexistentProperty;
    }
}