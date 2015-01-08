<?php
namespace Aura\SqlMapper_Bundle;

use Aura\Sql\ConnectionLocator;
use Aura\Sql\ExtendedPdo;
use Aura\Sql\Profiler;
use Aura\SqlMapper_Bundle\Query\ConnectedQueryFactory;
use Aura\SqlQuery\QueryFactory;
use Aura\SqlMapper_Bundle\SqliteFixture;

class GatewayTest extends \PHPUnit_Framework_TestCase
{
    use Assertions;

    protected $connections;
    protected $profiler;
    protected $query;
    protected $gateway;

    protected function setUp()
    {
        parent::setUp();

        $profiler = new Profiler;
        $this->profiler = $profiler;

        $this->connection_locator = new ConnectionLocator(function () use ($profiler) {
            $pdo = new ExtendedPdo('sqlite::memory:');
            $pdo->setProfiler($profiler);
            return $pdo;
        });

        $this->query = new ConnectedQueryFactory(new QueryFactory('sqlite'));

        $this->gateway = new FakeGateway(
            $this->connection_locator,
            $this->query,
            new Filter()
        );

        $fixture = new SqliteFixture(
            $this->gateway->getWriteConnection(),
            'aura_test_table'
        );
        $fixture->exec();
    }

    public function testGetPrimaryCol()
    {
        $expect = 'id';
        $actual = $this->gateway->getPrimaryCol('id');
        $this->assertSame($expect, $actual);
    }

    public function testGetTable()
    {
        $expect = 'aura_test_table';
        $actual = $this->gateway->getTable();
        $this->assertSame($expect, $actual);
    }

    public function testSelect()
    {
        $select = $this->gateway->select([
            'id',
            'name',
            'building',
        ]);

        $expect = '
            SELECT
                "aura_test_table"."id",
                "aura_test_table"."name",
                "aura_test_table"."building"
            FROM
                "aura_test_table"
        ';
        $actual = (string) $select;
        $this->assertSameSql($expect, $actual);
    }

    public function testInsert()
    {
        $row = [
            'id' => null,
            'name' => 'Mona',
            'building' => 10,
        ];

        $row = $this->gateway->insert($row);
        $this->assertTrue(is_array($row));
        $this->assertEquals(13, $row['id']);

        // did it insert?
        $actual = $this->gateway->select(['id', 'name', 'building'])
            ->where('id = ?', 13)
            ->fetchOne();

        $expect = [
            'id' => '13',
            'name' => 'Mona',
            'building' => '10'
        ];

        $this->assertEquals($actual, $expect);
    }

    public function testUpdate()
    {
        // fetch an object, then modify and update it
        $row = $this->gateway->fetchRowBy('name', 'Anna');
        $row['name'] = 'Annabelle';
        $row = $this->gateway->update($row);

        // did it update?
        $this->assertTrue(is_array($row));
        $actual = $this->gateway->fetchRowBy('name', 'Annabelle');
        $this->assertEquals($actual, $row);

        // did anything else update?
        $actual = $this->gateway->fetchRowBy('id', 2, ['id', 'name']);
        $expect = ['id' => '2', 'name' => 'Betty'];
        $this->assertEquals($actual, $expect);
    }

    public function testDelete()
    {
        // fetch an object, then delete it
        $row = $this->gateway->fetchRowBy('name', 'Anna');
        $this->gateway->delete($row);

        // did it delete?
        $actual = $this->gateway->fetchRowsBy('name', 'Anna');
        $this->assertSame(array(), $actual);

        // do we still have everything else?
        $actual = $this->gateway->select()->fetchAll();
        $expect = 11;
        $this->assertEquals($expect, count($actual));
    }
}
