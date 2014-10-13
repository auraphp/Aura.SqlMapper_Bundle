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
     * entity objects.
     *
     * @var MapperLocator
     *
     */
    protected $mappers;

    /**
     *
     * A collection of database connections extracted from the mappers.
     *
     * @var SplObjectStorage
     *
     */
    protected $connections;

    /**
     *
     * A collection of all entity objects to be sent to the database.
     *
     * @var SplObjectStorage
     *
     */
    protected $entities;

    /**
     *
     * A collection of all entity objects that were successfully inserted.
     *
     * @var SplObjectStorage
     *
     */
    protected $inserted;

    /**
     *
     * A collection of all entity objects that were successfully updated.
     *
     * @var SplObjectStorage
     *
     */
    protected $updates;

    /**
     *
     * A collection of all entity objects that were successfully deleted.
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
     * The entity object that caused the exception.
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
        $this->entities = new SplObjectStorage;
    }

    /**
     *
     * Attached an entity object for insertion.
     *
     * @param string $mapper_name The mapper name in the locator.
     *
     * @param object $entity The entity object to insert.
     *
     * @return null
     *
     */
    public function insert($mapper_name, $entity)
    {
        $this->detach($entity);
        $this->attach($entity, [
            'method'       => 'execInsert',
            'mapper_name' => $mapper_name,
        ]);
    }

    /**
     *
     * Attached an entity object for updating.
     *
     * @param string $mapper_name The mapper name in the locator.
     *
     * @param object $entity The entity object to update.
     *
     * @param array $initial_data Initial data for the entity.
     *
     * @return null
     *
     */
    public function update($mapper_name, $entity, array $initial_data = null)
    {
        $this->detach($entity);
        $this->attach($entity, [
            'method'       => 'execUpdate',
            'mapper_name' => $mapper_name,
            'initial_data' => $initial_data,
        ]);
    }

    /**
     *
     * Attached an entity object for deletion.
     *
     * @param string $mapper_name The mapper name in the locator.
     *
     * @param object $entity The entity object to delete.
     *
     * @return null
     *
     */
    public function delete($mapper_name, $entity)
    {
        $this->detach($entity);
        $this->attach($entity, [
            'method'       => 'execDelete',
            'mapper_name' => $mapper_name,
        ]);
    }

    /**
     *
     * Attaches an entity to this unit of work.
     *
     * @param object $entity The entity to attach.
     *
     * @param array $info Information about what to do with the entity.
     *
     * @return null
     *
     */
    protected function attach($entity, $info)
    {
        $this->entities->attach($entity, $info);
    }

    /**
     *
     * Detaches an entity from this unit of work.
     *
     * @param object $entity The entity to detach.
     *
     * @return null
     *
     */
    public function detach($entity)
    {
        $this->entities->detach($entity);
    }

    /**
     *
     * Loads all database connections from the mappers.
     *
     * @return null
     *
     */
    public function loadConnections()
    {
        $this->connections = new SplObjectStorage;
        foreach ($this->mappers as $mapper) {
            $connection = $mapper->getConnections()->getWrite();
            $this->connections->attach($connection);
        }
    }

    /**
     *
     * Gets the collection of database connections.
     *
     * @return SplObjectStorage
     *
     */
    public function getConnections()
    {
        return $this->connections;
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

        // load the connections from the mappers for transaction management
        $this->loadConnections();

        // perform the unit of work
        try {

            $this->execBegin();

            foreach ($this->entities as $entity) {

                // get the info for this entity
                $info = $this->entities[$entity];
                $method = $info['method'];
                $mapper = $this->mappers->get($info['mapper_name']);

                // remove used info
                unset($info['method']);
                unset($info['mapper']);

                // execute the method
                $this->$method($mapper, $entity, $info);
            }

            $this->execCommit();
            return true;

        } catch (Exception $e) {
            $this->failed = $entity; // from the loop above
            $this->exception = $e;
            $this->execRollback();
            return false;
        }
    }

    /**
     *
     * Begins a transaction on all connections.
     *
     * @return null
     *
     */
    protected function execBegin()
    {
        foreach ($this->connections as $connection) {
            $connection->beginTransaction();
        }
    }

    /**
     *
     * Inserts an entity via a mapper.
     *
     * @param Gateway $mapper Insert using this mapper.
     *
     * @param object $entity Insert this entity.
     *
     * @param array $info Information about the operation.
     *
     * @return null
     *
     */
    protected function execInsert(Gateway $mapper, $entity, array $info)
    {
        $last_insert_id = $mapper->insert($entity);
        $this->inserted->attach($entity, [
            'last_insert_id' => $last_insert_id,
        ]);
    }

    /**
     *
     * Updates an entity via a mapper.
     *
     * @param Gateway $mapper Update using this mapper.
     *
     * @param object $entity Update this entity.
     *
     * @param array $info Information about the operation.
     *
     * @return null
     *
     */
    protected function execUpdate(Gateway $mapper, $entity, array $info)
    {
        $initial_data = $info['initial_data'];
        $mapper->update($entity, $initial_data);
        $this->updated->attach($entity);
    }

    /**
     *
     * Deletes an entity via a mapper.
     *
     * @param Gateway $mapper Delete using this mapper.
     *
     * @param object $entity Delete this entity.
     *
     * @param array $info Information about the operation.
     *
     * @return null
     *
     */
    protected function execDelete(Gateway $mapper, $entity, array $info)
    {
        $mapper->delete($entity);
        $this->deleted->attach($entity);
    }

    /**
     *
     * Commits the transactions on all connections.
     *
     * @return null
     *
     */
    protected function execCommit()
    {
        foreach ($this->connections as $connection) {
            $connection->commit();
        }
    }

    /**
     *
     * Rolls back the transactions on all connections.
     *
     * @return null
     *
     */
    protected function execRollback()
    {
        foreach ($this->connections as $connection) {
            $connection->rollBack();
        }
    }

    /**
     *
     * Gets all the attached entities.
     *
     * @return SplObjectStorage
     *
     */
    public function getEntities()
    {
        return $this->entities;
    }

    /**
     *
     * Gets all the inserted entities.
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
     * Gets all the updated entities.
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
     * Gets all the deleted entities.
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
     * Gets the entity that caused the exception in exec().
     *
     * @return object
     *
     */
    public function getFailed()
    {
        return $this->failed;
    }
}
