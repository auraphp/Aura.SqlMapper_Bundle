<?php
namespace Aura\SqlMapper_Bundle;

use Aura\Sql\ConnectionLocator;
use Aura\Sql\ExtendedPdo;
use Aura\Sql\Profiler;
use Aura\SqlMapper_Bundle\Query\ConnectedQueryFactory;
use Aura\SqlQuery\QueryFactory;
use Aura\SqlMapper_Bundle\SqliteFixture;

class MapperTest extends \PHPUnit_Framework_TestCase
{
    use Assertions;

    protected $connections;
    protected $profiler;
    protected $query;
    protected $gateway_filter;
    protected $mapper_filter;
    protected $object_factory;
    protected $mapper;

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

        $this->mapper = new FakeMapper(
            $this->gateway,
            new ObjectFactory(),
            new Filter()
        );

        $fixture = new SqliteFixture(
            $this->mapper->getWriteConnection(),
            'aura_test_table'
        );
        $fixture->exec();
    }

    public function testGetIdentityValue()
    {
        $object = (object) [
            'id' => 88
        ];

        $expect = 88;
        $actual = $this->mapper->getIdentityValue($object);
        $this->assertSame($expect, $actual);

    }

    public function testFetchObject()
    {
        $actual = $this->mapper->fetchObjectBy('id', 1);
        $expect = (object) [
            'id' => '1',
            'firstName' => 'Anna',
            'buildingNumber' => '1',
            'floor' => '1',
        ];
        $this->assertEquals($expect, $actual);

        $actual = $this->mapper->fetchObjectBy('id', 0);
        $this->assertFalse($actual);
    }

    public function testFetchObjectAssortment()
    {
        $actual = $this->mapper->fetchObjectAssortmentBy('id', [1, 2, 3], 'id');
        $expect = [
            '1' => (object) [
                'id' => '1',
                'firstName' => 'Anna',
                'buildingNumber' => '1',
                'floor' => '1',
            ],
            '2' => (object) [
                'id' => '2',
                'firstName' => 'Betty',
                'buildingNumber' => '1',
                'floor' => '2',
            ],
            '3' => (object) [
                'id' => '3',
                'firstName' => 'Clara',
                'buildingNumber' => '1',
                'floor' => '3',
            ],
        ];

        $this->assertInstanceOf('Aura\SqlMapper_Bundle\Assortment', $actual);
        $this->assertEquals($expect, $actual->getArrayCopy());

        $this->assertEquals($expect['1'], $actual->pick('1'));

        $empty = (object) [];
        $this->assertEquals($empty, $actual->pick('no-such-value'));

        $actual = $this->mapper->fetchObjectAssortmentBy('id', [1, 2, 3], 'id', false);
        $this->assertFalse($actual->pick('no-such-value'));
    }

    public function testFetchCollection()
    {
        $actual = $this->mapper->fetchCollectionBy('id', [1, 2, 3]);
        $expect = [
            (object) [
                'id' => '1',
                'firstName' => 'Anna',
                'buildingNumber' => '1',
                'floor' => '1',
            ],
            (object) [
                'id' => '2',
                'firstName' => 'Betty',
                'buildingNumber' => '1',
                'floor' => '2',
            ],
            (object) [
                'id' => '3',
                'firstName' => 'Clara',
                'buildingNumber' => '1',
                'floor' => '3',
            ],
        ];

        $this->assertInstanceOf('ArrayObject', $actual);
        $this->assertEquals($expect, $actual->getArrayCopy());

        $actual = $this->mapper->fetchCollectionBy('id', [0]);
        $this->assertSame(array(), $actual);
    }

    public function testFetchCollectionAssortment()
    {
        $actual = $this->mapper->fetchCollectionAssortmentBy('building', 1, 'floor');
        $expect = [
            '1' => [
                (object) [
                    'id' => '1',
                    'firstName' => 'Anna',
                    'buildingNumber' => '1',
                    'floor' => '1',
                ],
                (object) [
                    'id' => '4',
                    'firstName' => 'Donna',
                    'buildingNumber' => '1',
                    'floor' => '1',
                ],
            ],
            '2' => [
                (object) [
                    'id' => '2',
                    'firstName' => 'Betty',
                    'buildingNumber' => '1',
                    'floor' => '2',
                ],
                (object) [
                    'id' => '5',
                    'firstName' => 'Edna',
                    'buildingNumber' => '1',
                    'floor' => '2',
                ],
            ],
            '3' => [
                (object) [
                    'id' => '3',
                    'firstName' => 'Clara',
                    'buildingNumber' => '1',
                    'floor' => '3',
                ],
                (object) [
                    'id' => '6',
                    'firstName' => 'Fiona',
                    'buildingNumber' => '1',
                    'floor' => '3',
                ],
            ],
        ];

        $this->assertInstanceOf('Aura\SqlMapper_Bundle\Assortment', $actual);
        $this->assertCount(3, $actual);
        for ($i = 1; $i <= 3; $i++) {
            $this->assertInstanceOf('ArrayObject', $actual[$i]);
            $this->assertEquals($expect[$i], $actual->pick($i)->getArrayCopy());
        }

        $this->assertInstanceOf('ArrayObject', $actual->pick('no-such-value'));
        $this->assertEquals(
            $this->mapper->newCollection()->getArrayCopy(),
            $actual->pick('no-such-value')->getArrayCopy()
        );

        $actual = $this->mapper->fetchCollectionAssortmentBy('building', 1, 'floor', false);
        $this->assertFalse($actual->pick('no-such-value'));
    }

    public function testInsert()
    {
        $object = (object) [
            'id' => null,
            'firstName' => 'Mona',
            'buildingNumber' => '10',
            'floor' => '99',
        ];

        $affected = $this->mapper->insert($object);
        $this->assertTrue($affected == 1);
        $this->assertEquals(13, $object->id);

        // did it insert?
        $actual = $this->mapper->fetchObjectBy('id', 13);
        $this->assertEquals('13', $actual->id);
        $this->assertEquals('Mona', $actual->firstName);

        // try to insert again, should fail
        $this->silenceErrors();
        $this->assertFalse($this->mapper->insert($object));
    }

    public function testUpdate()
    {
        // fetch an object, then modify and update it
        $object = $this->mapper->fetchObjectBy('name', 'Anna');
        $object->firstName = 'Annabelle';
        $affected = $this->mapper->update($object);

        // did it update?
        $this->assertTrue($affected == 1);
        $actual = $this->mapper->fetchObjectBy('name', 'Annabelle');
        $this->assertEquals($actual, $object);

        // did anything else update?
        $actual = $this->mapper->fetchObjectBy('id', 2);
        $this->assertEquals('2', $actual->id);
        $this->assertEquals('Betty', $actual->firstName);
    }

    public function testUpdateOnlyChanges()
    {
        // fetch an object, retain its original data, then change it
        $object = $this->mapper->fetchObjectBy('name', 'Anna');
        $initial_data = (array) $object;
        $object->firstName = 'Annabelle';

        // update with profiling turned on
        $this->profiler->setActive(true);
        $affected = $this->mapper->update($object, $initial_data);
        $this->profiler->setActive(false);

        // check the profile
        $profiles = $this->profiler->getProfiles();
        $expect = '
            UPDATE "aura_test_table"
            SET
                "name" = :name
            WHERE
                id = :id
        ';
        $this->assertSameSql($expect, $profiles[0]['statement']);
    }

    public function testDelete()
    {
        // fetch an object, then delete it
        $object = $this->mapper->fetchObjectBy('name', 'Anna');
        $this->mapper->delete($object);

        // did it delete?
        $actual = $this->mapper->fetchObjectBy('name', 'Anna');
        $this->assertFalse($actual);

        // do we still have everything else?
        $actual = $this->gateway->select()->fetchAll();
        $expect = 11;
        $this->assertEquals($expect, count($actual));
    }

    public function testSelect()
    {
        $select = $this->mapper->select();
        $expect = '
            SELECT
                "aura_test_table"."id" AS "id",
                "aura_test_table"."name" AS "firstName",
                "aura_test_table"."building" AS "buildingNumber",
                "aura_test_table"."floor" AS "floor"
            FROM
                "aura_test_table"
        ';
        $actual = (string) $select;
        $this->assertSameSql($expect, $actual);
    }

    protected function silenceErrors()
    {
        $conn = $this->gateway->getWriteConnection();
        $conn->setAttribute($conn::ATTR_ERRMODE, $conn::ERRMODE_SILENT);
    }
}
