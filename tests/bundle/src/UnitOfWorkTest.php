<?php
namespace Aura\SqlMapper_Bundle;

use Aura\Sql\ConnectionLocator;
use Aura\Sql\ExtendedPdo;
use Aura\SqlQuery\QueryFactory as UnderlyingQueryFactory;
use Aura\SqlMapper_Bundle\Query\QueryFactory;

class UnitOfWorkTest extends \PHPUnit_Framework_TestCase
{
    protected $connections;

    protected $mapper;

    protected $mappers;

    protected $work;

    protected function setUp()
    {
        $this->connections = new ConnectionLocator(function () {
            return new ExtendedPdo('sqlite::memory:');
        });

        $this->mapper = new FakeMapper(
            $this->connections,
            new QueryFactory(new UnderlyingQueryFactory('sqlite')),
            function ($row) {
                return new FakeEntity($row);
            }
        );

        $this->mappers = new MapperLocator([
            'fake' => function () { return $this->mapper; },
        ]);

        $this->work = new UnitOfWork($this->mappers);

        $fixture = new SqliteFixture(
            $this->connections->getWrite(),
            $this->mapper->getTable(),
            'aura_test_schema1',
            'aura_test_schema2'
        );
        $fixture->exec();
    }

    public function testInsert()
    {
        $entity = new FakeEntity;
        $entity->firstName = 'Laura';
        $entity->sizeScope = 10;
        $this->work->insert('fake', $entity);

        $storage = $this->work->getEntities();
        $this->assertSame(1, count($storage));
        $this->assertTrue($storage->contains($entity));

        $expect = ['method' => 'execInsert', 'mapper_name' => 'fake'];
        $actual = $storage[$entity];
        $this->assertSame($expect, $actual);
    }

    public function testUpdate()
    {
        // get the entity
        $entity = $this->mapper->fetchEntityBy('name', 'Anna');

        // modify it and attach for update
        $entity->firstName = 'Annabelle';
        $this->work->update('fake', $entity);

        // get it and see if it's set up right
        $storage = $this->work->getEntities();
        $this->assertSame(1, count($storage));
        $this->assertTrue($storage->contains($entity));

        $expect = [
            'method' => 'execUpdate',
            'mapper_name' => 'fake',
            'initial_data' => null
        ];
        $actual = $storage[$entity];
        $this->assertSame($expect, $actual);
    }

    public function testDelete()
    {
        // get the entity
        $entity = $this->mapper->fetchEntityBy('name', 'Anna');

        // attach for delete
        $this->work->delete('fake', $entity);

        // get it and see if it's set up right
        $storage = $this->work->getEntities();
        $this->assertSame(1, count($storage));
        $this->assertTrue($storage->contains($entity));

        $expect = ['method' => 'execDelete', 'mapper_name' => 'fake'];
        $actual = $storage[$entity];
        $this->assertSame($expect, $actual);
    }

    public function testDetach()
    {
        // create an entity
        $entity = new FakeEntity;
        $entity->firstName = 'Laura';
        $entity->sizeScope = 10;

        // attach it
        $this->work->insert('fake', $entity);

        // make sure it's attached
        $storage = $this->work->getEntities();
        $this->assertSame(1, count($storage));
        $this->assertTrue($storage->contains($entity));
        $expect = ['method' => 'execInsert', 'mapper_name' => 'fake'];
        $actual = $storage[$entity];
        $this->assertSame($expect, $actual);

        // detach it
        $this->work->detach($entity);

        // make sure it's detached
        $storage = $this->work->getEntities();
        $this->assertSame(0, count($storage));
    }

    public function testLoadAndGetConnections()
    {
        $this->work->loadConnections();
        $conns = $this->work->getConnections();
        $this->assertTrue($conns->contains($this->connections->getWrite()));
    }

    public function testExec_success()
    {
        // entity collection
        $coll = [];

        // insert
        $coll[0] = new FakeEntity;
        $coll[0]->firstName = 'Laura';
        $coll[0]->sizeScope = 10;
        $this->work->insert('fake', $coll[0]);

        // update
        $coll[1] = $this->mapper->fetchEntityBy('name', 'Anna');
        $coll[1]->firstName = 'Annabelle';
        $this->work->update('fake', $coll[1]);

        // delete
        $coll[2] = $this->mapper->fetchEntityBy('name', 'Betty');
        $this->work->delete('fake', $coll[2]);

        // execute
        $result = $this->work->exec();
        $this->assertTrue($result);

        // check inserted
        $inserted = $this->work->getInserted();
        $this->assertTrue($inserted->contains($coll[0]));
        $this->assertEquals('11', $coll[0]->identity);

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
        $entity = new FakeEntity;
        $this->work->insert('fake', $entity);

        // execute
        $result = $this->work->exec();
        $this->assertFalse($result);

        // get the failed object
        $failed = $this->work->getFailed();
        $this->assertSame($entity, $failed);

        // get the exception message, which changes between PHP versions
        $expect = 'SQLSTATE[23000]: Integrity constraint violation: 19';
        $actual = substr($this->work->getException()->getMessage(), 0, strlen($expect));
        $this->assertSame($expect, $actual);
    }
}
