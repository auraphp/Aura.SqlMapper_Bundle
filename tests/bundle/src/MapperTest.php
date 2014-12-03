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

        $this->mapper = new FakeMapper($this->connection_locator, $this->query);

        $fixture = new SqliteFixture(
            $this->connection_locator->getWrite(),
            $this->mapper->getTable(),
            'aura_test_schema1',
            'aura_test_schema2'
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

    public function testGetPrimaryCol()
    {
        $expect = 'id';
        $actual = $this->mapper->getPrimaryCol('id');
        $this->assertSame($expect, $actual);
    }

    public function testGetTable()
    {
        $expect = 'aura_test_table';
        $actual = $this->mapper->getTable();
        $this->assertSame($expect, $actual);
    }

    public function testFetchObject()
    {
        $actual = $this->mapper->fetchObject(
            $this->mapper->select()->where('id = ?', 1)
        );
        unset($actual->defaultIgnore); // creation date-time
        $expect = (object) [
            'id' => '1',
            'firstName' => 'Anna',
            'sizeScale' => null,
            'defaultNull' => null,
            'defaultString' => 'string',
            'defaultNumber' => '12345',
        ];
        $this->assertEquals($expect, $actual);

        $actual = $this->mapper->fetchObject(
            $this->mapper->select()->where('id = 0')
        );
        $this->assertFalse($actual);
    }

    public function testFetchObjectBy()
    {
        $actual = $this->mapper->fetchObjectBy('id', 1);
        unset($actual->defaultIgnore); // creation date-time
        $expect = (object) [
            'id' => '1',
            'firstName' => 'Anna',
            'sizeScale' => null,
            'defaultNull' => null,
            'defaultString' => 'string',
            'defaultNumber' => '12345',
        ];
        $this->assertEquals($expect, $actual);

        $actual = $this->mapper->fetchObjectBy('id', -1);
        $this->assertFalse($actual);
    }

    public function testFetchCollection()
    {
        $actual = $this->mapper->fetchCollection(
            $this->mapper->select()->where('id = ?', 1)
        );
        unset($actual[0]->defaultIgnore); // creation date-time
        $expect = [
            (object) [
                'id' => '1',
                'firstName' => 'Anna',
                'sizeScale' => null,
                'defaultNull' => null,
                'defaultString' => 'string',
                'defaultNumber' => '12345',
            ],
        ];
        $this->assertEquals($expect, $actual);

        $actual = $this->mapper->fetchCollection(
            $this->mapper->select()->where('id = 0')
        );
        $this->assertSame(array(), $actual);
    }

    public function testFetchCollectionBy()
    {
        $actual = $this->mapper->fetchCollectionBy('id', [1]);
        unset($actual[0]->defaultIgnore); // creation date-time
        $expect = [
            (object) [
                'id' => '1',
                'firstName' => 'Anna',
                'sizeScale' => null,
                'defaultNull' => null,
                'defaultString' => 'string',
                'defaultNumber' => '12345',
            ],
        ];
        $this->assertEquals($expect, $actual);

        $actual = $this->mapper->fetchCollectionBy('id', [0]);
        $this->assertSame(array(), $actual);
    }

    public function testInsert()
    {
        $object = (object) [
            'id' => null,
            'firstName' => 'Laura',
            'sizeScale' => 10,
            'defaultNull' => null,
            'defaultString' => null,
            'defaultNumber' => null,
            'defaultIgnore' => null,
        ];

        $affected = $this->mapper->insert($object);
        $this->assertTrue($affected == 1);
        $this->assertEquals(11, $object->id);

        // did it insert?
        $actual = $this->mapper->select(['id', 'name'])
            ->where('id = ?', 11)
            ->fetchOne();

        $expect = [
            'id' => '11',
            'firstName' => 'Laura'
        ];

        $this->assertEquals($actual, $expect);
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
        $actual = $this->mapper->select(['id', 'name'])
            ->where('id = ?', 2)
            ->fetchOne();
        $expect = ['id' => '2', 'firstName' => 'Betty'];
        $this->assertEquals($actual, $expect);
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
        $actual = $this->mapper->select()
            ->where('name = ?', 'Anna')
            ->fetchOne();
        $this->assertFalse($actual);

        // do we still have everything else?
        $actual = $this->mapper->select()->fetchAll();
        $expect = 9;
        $this->assertEquals($expect, count($actual));
    }

    public function testSelect()
    {
        $select = $this->mapper->select();
        $expect = '
            SELECT
                "aura_test_table"."id" AS "id",
                "aura_test_table"."name" AS "firstName",
                "aura_test_table"."test_size_scale" AS "sizeScale",
                "aura_test_table"."test_default_null" AS "defaultNull",
                "aura_test_table"."test_default_string" AS "defaultString",
                "aura_test_table"."test_default_number" AS "defaultNumber",
                "aura_test_table"."test_default_ignore" AS "defaultIgnore"
            FROM
                "aura_test_table"
        ';
        $actual = (string) $select;
        $this->assertSameSql($expect, $actual);
    }
}
