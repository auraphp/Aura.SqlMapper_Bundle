<?php
namespace Aura\SqlMapper_Bundle;

use Aura\Sql\ConnectionLocator;
use Aura\Sql\ExtendedPdo;
use Aura\SqlQuery\QueryFactory;
use Aura\SqlMapper_Bundle\Query\ConnectedQueryFactory;

class UnitOfWorkTest extends \PHPUnit_Framework_TestCase
{
    protected $connections;

    protected $mapper;

    protected $mapper_locator;

    protected $work;

    protected function setUp()
    {
        $this->connection_locator = new ConnectionLocator(function () {
            return new ExtendedPdo('sqlite::memory:');
        });

        $this->mapper = new FakeMapper(
            $this->connection_locator,
            new ConnectedQueryFactory(new QueryFactory('sqlite')),
            function ($row) {
                return new FakeEntity($row);
            }
        );

        $this->mapper_locator = new MapperLocator([
            'fake' => function () { return $this->mapper; },
        ]);

        $this->work = new UnitOfWork($this->mapper_locator);

        $fixture = new SqliteFixture(
            $this->connection_locator->getWrite(),
            $this->mapper->getTable(),
            'aura_test_schema1',
            'aura_test_schema2'
        );
        $fixture->exec();
    }

    public function testInsert()
    {
        $object = new FakeEntity;
        $object->firstName = 'Laura';
        $object->sizeScope = 10;
        $this->work->insert('fake', $object);

        $storage = $this->work->getObjects();
        $this->assertSame(1, count($storage));
        $this->assertTrue($storage->contains($object));

        $expect = ['method' => 'execInsert', 'mapper_name' => 'fake'];
        $actual = $storage[$object];
        $this->assertSame($expect, $actual);
    }

    public function testUpdate()
    {
        // get the object
        $object = $this->mapper->fetchObjectBy('name', 'Anna');

        // modify it and attach for update
        $object->firstName = 'Annabelle';
        $this->work->update('fake', $object);

        // get it and see if it's set up right
        $storage = $this->work->getObjects();
        $this->assertSame(1, count($storage));
        $this->assertTrue($storage->contains($object));

        $expect = [
            'method' => 'execUpdate',
            'mapper_name' => 'fake',
            'initial_data' => null
        ];
        $actual = $storage[$object];
        $this->assertSame($expect, $actual);
    }

    public function testDelete()
    {
        // get the object
        $object = $this->mapper->fetchObjectBy('name', 'Anna');

        // attach for delete
        $this->work->delete('fake', $object);

        // get it and see if it's set up right
        $storage = $this->work->getObjects();
        $this->assertSame(1, count($storage));
        $this->assertTrue($storage->contains($object));

        $expect = ['method' => 'execDelete', 'mapper_name' => 'fake'];
        $actual = $storage[$object];
        $this->assertSame($expect, $actual);
    }

    public function testDetach()
    {
        // create an object
        $object = new FakeEntity;
        $object->firstName = 'Laura';
        $object->sizeScope = 10;

        // attach it
        $this->work->insert('fake', $object);

        // make sure it's attached
        $storage = $this->work->getObjects();
        $this->assertSame(1, count($storage));
        $this->assertTrue($storage->contains($object));
        $expect = ['method' => 'execInsert', 'mapper_name' => 'fake'];
        $actual = $storage[$object];
        $this->assertSame($expect, $actual);

        // detach it
        $this->work->detach($object);

        // make sure it's detached
        $storage = $this->work->getObjects();
        $this->assertSame(0, count($storage));
    }

    public function testLoadAndGetWriteConnections()
    {
        $this->work->loadWriteConnections();
        $write_connections = $this->work->getWriteConnections();
        $this->assertTrue($write_connections->contains($this->connection_locator->getWrite()));
    }

    public function testExec_success()
    {
        // object collection
        $coll = [];

        // insert
        $coll[0] = new FakeEntity;
        $coll[0]->firstName = 'Laura';
        $coll[0]->sizeScope = 10;
        $this->work->insert('fake', $coll[0]);

        // update
        $coll[1] = $this->mapper->fetchObjectBy('name', 'Anna');
        $coll[1]->firstName = 'Annabelle';
        $this->work->update('fake', $coll[1]);

        // delete
        $coll[2] = $this->mapper->fetchObjectBy('name', 'Betty');
        $this->work->delete('fake', $coll[2]);

        // execute
        $result = $this->work->exec();
        $this->assertTrue($result);

        // check inserted
        $inserted = $this->work->getInserted();
        $this->assertTrue($inserted->contains($coll[0]));
        $this->assertEquals('11', $coll[0]->id);

        // check updated
        $updated = $this->work->getUpdated();
        $this->assertTrue($updated->contains($coll[1]));

        // check deleted
        $deleted = $this->work->getDeleted();
        $this->assertTrue($deleted->contains($coll[2]));
    }

    public function testExec_failure()
    {
        // insert without name; this should cause an exception and failure
        $object = new FakeEntity;
        $this->work->insert('fake', $object);

        // execute
        $result = $this->work->exec();
        $this->assertFalse($result);

        // get the failed object
        $failed = $this->work->getFailed();
        $this->assertSame($object, $failed);

        // get the exception message, which changes between PHP versions
        $expect = 'SQLSTATE[23000]: Integrity constraint violation: 19';
        $actual = substr($this->work->getException()->getMessage(), 0, strlen($expect));
        $this->assertSame($expect, $actual);
    }
}
