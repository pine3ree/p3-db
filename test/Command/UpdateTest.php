<?php

/**
 * @package     p3-db
 * @subpackage  p3-db-test
 * @author      pine3ree https://github.com/pine3ree
 */

namespace P3\DbTest\Command;

use P3\Db\Db;
use P3\Db\Sql\Driver\MySql;
use P3\Db\Sql\Statement\Update;
use Prophecy\Prophecy\ObjectProphecy;
use SebastianBergmann\CodeCoverage\TestCase;

class UpdateTest extends TestCase
{
    /** @var ObjectProphecy|PDO */
    private $pdo;

    /** @var ObjectProphecy|PDOStatement */
    private $pdoStatement;

    public function setUp(): void
    {
        // setup mock-objects

        $this->pdo = $this->prophesize(PDO::class);
        $this->pdoStatement = $this->prophesize(PDOStatement::class);

        $this->pdoStatement
            ->execute()
            ->willReturn($this->returnSelf());

        $this->pdoStatement
            ->closeCursor()
            ->willReturn(null);
    }

    public function tearDown()
    {
        $this->pdoStatement = null;
        $this->pdo = null;
    }

    protected function createUpdateCommand(Db &$dbMock = null): Update
    {
        $db = $this->prophesize(Db::class);

        $db->getDriver(true)->willReturn(new MySql($this->pdo->reveal()));
        $db->getDriver(false)->willReturn($shallowDriver = new MySql());
        $db->getDriver()->willReturn($shallowDriver);

        $update = new Update($db->reveal());

        $db->prepare($update->getSqlStatement(), true)
            ->willReturn($this->pdoStatement->reveal());

        $dbMock = $db->reveal();

        return $update;
    }

    public function testGetSqlStatement()
    {
        $update = $this->createUpdateCommand($db);
        self::assertInstanceOf(Update::class, $update->getSqlStatement());
    }

    public function testGetSqlRisesExceptionWithoutSetClause()
    {
        $update = $this->createUpdateCommand($db);
        $update->table('user', 'u');

        $this->expectException(RuntimeException::class);
        $update->getSql();
    }

    public function testGetSqlRisesExceptionWithoutWhereClause()
    {
        $update = $this->createUpdateCommand($db);
        $update->table('user')->set(['enabled' => true]);

        $this->expectException(RuntimeException::class);
        $update->getSql();
    }

    public function testGetSqlRisesExceptionWithEmptyWhereClause()
    {
        $update = $this->createUpdateCommand($db);
        $update->table('user')->set(['enabled' => true])->where("");

        $this->expectException(RuntimeException::class);
        $update->getSql();
    }

    public function testGetSql()
    {
        $update = $this->createUpdateCommand($db);
        $update->table('user')->set(['enabled' => true])->where("id = 42");

        self::assertStringStartsWith("UPDATE `user` SET `enabled` = :set", $update->getSql());
        self::assertStringEndsWith(" WHERE id = 42", $update->getSql());
    }
}