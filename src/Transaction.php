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

use Closure;
use Exception;
use SplObjectStorage;

/**
 *
 * A unit-of-work implementation.
 *
 * @package Aura.SqlMapper_Bundle
 *
 */
class Transaction
{
    /**
     *
     * A MapperLocator for the mappers used to insert, update, and delete
     * individual objects.
     *
     * @var MapperLocator
     *
     */
    protected $mapper_locator;

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
     * The result returned from performing the transaction queries, or the
     * exception that caused a rollback.
     *
     * @var mixed
     *
     */
    protected $result;

    /**
     *
     * Constructor.
     *
     * @param MapperLocator $mapper_locator The mapper locator.
     *
     */
    public function __construct(MapperLocator $mapper_locator)
    {
        $this->mapper_locator = $mapper_locator;
        $this->write_connections = new SplObjectStorage;
    }

    /**
     *
     * Returns a mapper by name as a magic property on this Transaction.
     *
     * @param string $mapper The mapper name.
     *
     * @return MapperInterface
     *
     */
    public function __get($mapper)
    {
        return $this->mapper_locator->get($mapper);
    }

    /**
     *
     * Returns the Transaction result, if any.
     *
     * @return mixes
     *
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     *
     * Executes a Closure of queries inside an SQL transaction; rolls back on
     * exception, otherwise commits.
     *
     * The return of the Closure may be retrieved via getResult(), as may any
     * exception thrown within the Closure.
     *
     * @param Closure $queries The queries to execute inside an SQL transaction.
     * The Closure is bound to the Transaction object so that `$this` may be
     * used to reference mappers as if they are properties on the Closure.
     *
     * @return bool True if the transaction was committed, false if rolled back.
     *
     */
    public function exec(Closure $queries)
    {
        $this->result = null;
        try {
            $this->loadWriteConnections();
            $this->begin();
            $this->result = $queries->bindTo($this)->__invoke();
            $this->commit();
            return true;
        } catch (Exception $e) {
            $this->result = $e;
            $this->rollback();
            return false;
        }
    }

    /**
     *
     * Loads all write connections from the mappers.
     *
     * @return null
     *
     */
    protected function loadWriteConnections()
    {
        foreach ($this->mapper_locator as $mapper) {
            $connection = $mapper->getWriteConnection();
            $this->write_connections->attach($connection);
        }
    }

    /**
     *
     * Begins a transaction on all write connections.
     *
     * @return null
     *
     */
    protected function begin()
    {
        foreach ($this->write_connections as $connection) {
            $connection->beginTransaction();
        }
    }

    /**
     *
     * Commits the transactions on all write connections.
     *
     * @return null
     *
     */
    protected function commit()
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
    protected function rollback()
    {
        foreach ($this->write_connections as $connection) {
            $connection->rollBack();
        }
    }
}
