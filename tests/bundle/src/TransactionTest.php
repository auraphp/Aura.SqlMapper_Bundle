<?php
namespace Aura\SqlMapper_Bundle;

use Aura\Sql\ConnectionLocator;
use Aura\Sql\ExtendedPdo;
use Aura\SqlQuery\QueryFactory;
use Aura\SqlMapper_Bundle\Query\ConnectedQueryFactory;
use Exception;

class TransactionTest extends \PHPUnit_Framework_TestCase
{
    protected $mapper;

    protected $transaction;

    protected function setUp()
    {
        $connection_locator = new ConnectionLocator(function () {
            return new ExtendedPdo('sqlite::memory:');
        });

        $mapper = new FakeMapper(
            $connection_locator,
            new ConnectedQueryFactory(new QueryFactory('sqlite'))
        );

        $mapper_locator = new MapperLocator([
            'fake' => function () use ($mapper) { return $mapper; },
        ]);

        $this->mapper = $mapper;
        $this->transaction = new Transaction($mapper_locator);

        $fixture = new SqliteFixture(
            $connection_locator->getWrite(),
            $mapper->getTable(),
            'aura_test_schema1',
            'aura_test_schema2'
        );
        $fixture->exec();
    }

    public function test__get()
    {
        $actual = $this->transaction->fake;
        $this->assertSame($this->mapper, $actual);
    }

    public function testExec_commit()
    {
        $queries = function () {
            // do some queries that work, and then:
            return 'success';
        };

        $actual = $this->transaction->exec($queries);
        $this->assertTrue($actual);

        $actual = $this->transaction->getResult();
        $this->assertSame('success', $actual);
    }

    public function testExec_rollback()
    {
        $queries = function () {
            // fake a failure
            throw new Exception('failure');
        };

        $actual = $this->transaction->exec($queries);
        $this->assertFalse($actual);

        $actual = $this->transaction->getResult();
        $this->assertInstanceOf('Exception', $actual);
        $this->assertSame('failure', $actual->getMessage());
    }
}
