<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @package Aura.SqlMapper_Bundle
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\SqlMapper_Bundle;

use SplObjectStorage;
use Exception;

/**
 *
 * A unit-of-work implementation.
 *
 * @package Aura.SqlMapper_Bundle
 *
 */
class UnitOfWork
{
    /**
     *
     * A MapperLocator for the mappers used to insert, update, and delete
     * individual objects.
     *
     * @var MapperLocator
     *
     */
    protected $mappers;

    /**
     *
     * A collection of database write connections extracted from the mappers.
     *
     * @var SplObjectStorage
     *
     */
    protected $write_connections;

    /**
     *
     * A collection of all individual objects to be sent to the database.
     *
     * @var SplObjectStorage
     *
     */
    protected $objects;

    /**
     *
     * A collection of all individual objects that were successfully inserted.
     *
     * @var SplObjectStorage
     *
     */
    protected $inserted;

    /**
     *
     * A collection of all individual objects that were successfully updated.
     *
     * @var SplObjectStorage
     *
     */
    protected $updates;

    /**
     *
     * A collection of all individual objects that were successfully deleted.
     *
     * @var SplObjectStorage
     *
     */
    protected $deleted;

    /**
     *
     * The exception that occurred during exec(), causing a rollback.
     *
     * @var Exception
     *
     */
    protected $exception;

    /**
     *
     * The individual object that caused the exception.
     *
     * @var object
     *
     */
    protected $failed;

    /**
     *
     * Constructor.
     *
     * @param MapperLocator $mappers The mapper locator.
     *
     */
    public function __construct(MapperLocator $mappers)
    {
        $this->mappers = $mappers;
        $this->objects = new SplObjectStorage;
    }

    /**
     *
     * Attaches an individual object for insertion.
     *
     * @param string $mapper_name The mapper name in the locator.
     *
     * @param object $object The individual object to insert.
     *
     * @return null
     *
     */
    public function insert($mapper_name, $object)
    {
        $this->detach($object);
        $this->attach($object, [
            'method'       => 'execInsert',
            'mapper_name'  => $mapper_name,
        ]);
    }

    /**
     *
     * Attaches an individual object for updating.
     *
     * @param string $mapper_name The mapper name in the locator.
     *
     * @param object $object The individual object to update.
     *
     * @param array $initial_data Initial data for the individual object.
     *
     * @return null
     *
     */
    public function update($mapper_name, $object, array $initial_data = null)
    {
        $this->detach($object);
        $this->attach($object, [
            'method'       => 'execUpdate',
            'mapper_name'  => $mapper_name,
            'initial_data' => $initial_data,
        ]);
    }

    /**
     *
     * Attaches an individual object for deletion.
     *
     * @param string $mapper_name The mapper name in the locator.
     *
     * @param object $object The individual object to delete.
     *
     * @return null
     *
     */
    public function delete($mapper_name, $object)
    {
        $this->detach($object);
        $this->attach($object, [
            'method'       => 'execDelete',
            'mapper_name'  => $mapper_name,
        ]);
    }

    /**
     *
     * Attaches an individual object to this unit of work.
     *
     * @param object $object The individual object to attach.
     *
     * @param array $info Information about what to do with the individual object.
     *
     * @return null
     *
     */
    protected function attach($object, $info)
    {
        $this->objects->attach($object, $info);
    }

    /**
     *
     * Detaches an individual object from this unit of work.
     *
     * @param object $object The individual object to detach.
     *
     * @return null
     *
     */
    public function detach($object)
    {
        $this->objects->detach($object);
    }

    /**
     *
     * Loads all write connections from the mappers.
     *
     * @return null
     *
     */
    public function loadWriteConnections()
    {
        $this->write_connections = new SplObjectStorage;
        foreach ($this->mappers as $mapper) {
            $connection = $mapper->getWriteConnection();
            $this->write_connections->attach($connection);
        }
    }

    /**
     *
     * Gets the collection of write connections.
     *
     * @return SplObjectStorage
     *
     */
    public function getWriteConnections()
    {
        return $this->write_connections;
    }

    /**
     *
     * Executes the unit of work.
     *
     * @return bool True if the unit succeeded, false if not.
     *
     * @todo Add pre/post hooks, so we can handle things like optimistic and
     * pessimistic locking?
     *
     */
    public function exec()
    {
        // clear tracking properties
        $this->exception = null;
        $this->failed    = null;
        $this->deleted   = new SplObjectStorage;
        $this->inserted  = new SplObjectStorage;
        $this->updated   = new SplObjectStorage;

        // load the write connections from the mappers for transaction management
        $this->loadWriteConnections();

        // perform the unit of work
        try {

            $this->execBegin();

            foreach ($this->objects as $object) {

                // get the info for this object
                $info = $this->objects[$object];
                $method = $info['method'];
                $mapper = $this->mappers->get($info['mapper_name']);

                // remove used info
                unset($info['method']);
                unset($info['mapper']);

                // execute the method
                $this->$method($mapper, $object, $info);
            }

            $this->execCommit();
            return true;

        } catch (Exception $e) {
            $this->failed = $object; // from the loop above
            $this->exception = $e;
            $this->execRollback();
            return false;
        }
    }

    /**
     *
     * Begins a transaction on all write connections.
     *
     * @return null
     *
     */
    protected function execBegin()
    {
        foreach ($this->write_connections as $connection) {
            $connection->beginTransaction();
        }
    }

    /**
     *
     * Inserts an individual object via a mapper.
     *
     * @param AbstractMapper $mapper Insert using this mapper.
     *
     * @param object $object Insert this individual object.
     *
     * @param array $info Information about the operation.
     *
     * @return null
     *
     */
    protected function execInsert(AbstractMapper $mapper, $object, array $info)
    {
        $last_insert_id = $mapper->insert($object);
        $this->inserted->attach($object, [
            'last_insert_id' => $last_insert_id,
        ]);
    }

    /**
     *
     * Updates an individual object via a mapper.
     *
     * @param AbstractMapper $mapper Update using this mapper.
     *
     * @param object $object Update this individual object.
     *
     * @param array $info Information about the operation.
     *
     * @return null
     *
     */
    protected function execUpdate(AbstractMapper $mapper, $object, array $info)
    {
        $initial_data = $info['initial_data'];
        $mapper->update($object, $initial_data);
        $this->updated->attach($object);
    }

    /**
     *
     * Deletes an individual object via a mapper.
     *
     * @param AbstractMapper $mapper Delete using this mapper.
     *
     * @param object $object Delete this individual object.
     *
     * @param array $info Information about the operation.
     *
     * @return null
     *
     */
    protected function execDelete(AbstractMapper $mapper, $object, array $info)
    {
        $mapper->delete($object);
        $this->deleted->attach($object);
    }

    /**
     *
     * Commits the transactions on all write connections.
     *
     * @return null
     *
     */
    protected function execCommit()
    {
        foreach ($this->write_connections as $connection) {
            $connection->commit();
        }
    }

    /**
     *
     * Rolls back the transactions on all write connections.
     *
     * @return null
     *
     */
    protected function execRollback()
    {
        foreach ($this->write_connections as $connection) {
            $connection->rollBack();
        }
    }

    /**
     *
     * Gets all the attached individual objects.
     *
     * @return SplObjectStorage
     *
     */
    public function getObjects()
    {
        return $this->objects;
    }

    /**
     *
     * Gets all the inserted individual objects.
     *
     * @return SplObjectStorage
     *
     */
    public function getInserted()
    {
        return $this->inserted;
    }

    /**
     *
     * Gets all the updated individual objects.
     *
     * @return SplObjectStorage
     *
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     *
     * Gets all the deleted individual objects.
     *
     * @return SplObjectStorage
     *
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     *
     * Gets the exception that caused a rollback in exec().
     *
     * @return Exception
     *
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     *
     * Gets the individual object that caused the exception in exec().
     *
     * @return object
     *
     */
    public function getFailed()
    {
        return $this->failed;
    }
}
