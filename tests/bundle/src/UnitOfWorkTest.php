<?php
namespace Aura\SqlMapper_Bundle;

use Aura\Sql\ConnectionLocator;
use Aura\Sql\ExtendedPdo;
use Aura\SqlQuery\QueryFactory;

class UnitOfWorkTest extends \PHPUnit_Framework_TestCase
{
    protected $work;

    protected $factory;

    protected $connections;

    protected $mapper;

    protected $gateway;

    protected $gateways;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->connections = new ConnectionLocator(function () {
            return new ExtendedPdo('sqlite::memory:');
        });

        $this->mapper = new FakeMapper;

        $this->gateway = new Gateway($this->connections, new QueryFactory('sqlite'), $this->mapper);

        $this->gateways = new MapperLocator([
            'mock' => function () { return $this->gateway; },
        ]);

        $this->work = new UnitOfWork($this->gateways);

        $db_setup_class = 'Aura\Sql\DbSetup\Sqlite';
        $db_setup = new DbSetup\Sqlite(
            $this->connections->getWrite(),
            $this->mapper->getTable(),
            'aura_test_schema1',
            'aura_test_schema2'
        );
        $db_setup->exec();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        parent::tearDown();
    }

    public function testInsert()
    {
        $entity = new FakeEntity;
        $entity->firstName = 'Laura';
        $entity->sizeScope = 10;
        $this->work->insert('mock', $entity);

        $storage = $this->work->getEntities();
        $this->assertSame(1, count($storage));
        $this->assertTrue($storage->contains($entity));

        $expect = ['method' => 'execInsert', 'gateway_name' => 'mock'];
        $actual = $storage[$entity];
        $this->assertSame($expect, $actual);
    }

    public function testUpdate()
    {
        // get the entity
        $select = $this->gateway->newSelect();
        $select->where('name = ?', 'Anna');
        $entity = new FakeEntity($this->gateway->fetchOne($select));

        // modify it and attach for update
        $entity->firstName = 'Annabelle';
        $this->work->update('mock', $entity);

        // get it and see if it's set up right
        $storage = $this->work->getEntities();
        $this->assertSame(1, count($storage));
        $this->assertTrue($storage->contains($entity));

        $expect = [
            'method' => 'execUpdate',
            'gateway_name' => 'mock',
            'initial_data' => null
        ];
        $actual = $storage[$entity];
        $this->assertSame($expect, $actual);
    }

    public function testDelete()
    {
        // get the entity
        $select = $this->gateway->newSelect();
        $select->where('name = ?', 'Anna');
        $entity = new FakeEntity($this->gateway->fetchOne($select));

        // attach for delete
        $this->work->delete('mock', $entity);

        // get it and see if it's set up right
        $storage = $this->work->getEntities();
        $this->assertSame(1, count($storage));
        $this->assertTrue($storage->contains($entity));

        $expect = ['method' => 'execDelete', 'gateway_name' => 'mock'];
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
        $this->work->insert('mock', $entity);

        // make sure it's attached
        $storage = $this->work->getEntities();
        $this->assertSame(1, count($storage));
        $this->assertTrue($storage->contains($entity));
        $expect = ['method' => 'execInsert', 'gateway_name' => 'mock'];
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
        $this->work->insert('mock', $coll[0]);

        // update
        $select = $this->gateway->newSelect();
        $select->where('name = ?', 'Anna');
        $coll[1] = new FakeEntity($this->gateway->fetchOne($select));
        $coll[1]->firstName = 'Annabelle';
        $this->work->update('mock', $coll[1]);

        // delete
        $select = $this->gateway->newSelect();
        $select->where('name = ?', 'Betty');
        $coll[2] = new FakeEntity($this->gateway->fetchOne($select));
        $this->work->delete('mock', $coll[2]);

        // execute
        $result = $this->work->exec();
        $this->assertTrue($result);

        // check inserted
        $inserted = $this->work->getInserted();
        $this->assertTrue($inserted->contains($coll[0]));
        $expect = ['last_insert_id' => 11];
        $this->assertEquals($expect, $inserted[$coll[0]]);

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
        $this->work->insert('mock', $entity);

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
