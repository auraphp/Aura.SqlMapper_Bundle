<?php
namespace Aura\SqlMapper_Bundle;

use Aura\Sql\ConnectionLocator;
use Aura\Sql\ExtendedPdo;
use Aura\SqlQuery\QueryFactory;
use Aura\SqlMapper_Bundle\Query\ConnectedQueryFactory;
use Exception;

class TransactionTest extends \PHPUnit_Framework_TestCase
{
    protected $mapper_locator;

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

        $this->mapper_locator = new MapperLocator([
            'fake' => function () use ($mapper) { return $mapper; },
        ]);

        $this->transaction = new Transaction($this->mapper_locator);
    }

    public function test__invoke_commit()
    {
        $queries = function (MapperLocator $mapper_locator) {
            // do some queries that work, and then:
            return 'success';
        };

        $actual = $this->transaction->__invoke($queries, $this->mapper_locator);
        $this->assertTrue($actual);

        $actual = $this->transaction->getResult();
        $this->assertSame('success', $actual);
    }

    public function test__invoke_rollback()
    {
        $queries = function (MapperLocator $mapper_locator) {
            // fake a failure
            throw new Exception('failure');
        };

        $actual = $this->transaction->__invoke($queries, $this->mapper_locator);
        $this->assertFalse($actual);

        $actual = $this->transaction->getResult();
        $this->assertInstanceOf('Exception', $actual);
        $this->assertSame('failure', $actual->getMessage());
    }
}
