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
 * An SQL transaction wrapper.
 *
 * @package Aura.SqlMapper_Bundle
 *
 */
class Transaction
{
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
     * Executes a $queries callable inside an SQL transaction; rolls back on
     * exception, otherwise commits. The callable should use the signature
     * `function (MapperLocator $mappers)` -- the MapperLocator from this
     * Transaction is passed into the callable.
     *
     * The return of the callable, or the exception thrown by the callable, may
     * be retrieved via getResult().
     *
     * @param callable $queries A callable to execute inside an SQL transaction.
     * The MapperLocator for this Transaction is passed to the callable as a
     * function parameter.
     *
     * @param MapperLocator $mapper_locator A mapper locator for the queries.
     *
     * @return bool True if the transaction was committed, false if rolled back.
     *
     */
    public function __invoke($queries, MapperLocator $mapper_locator)
    {
        // remove $queries from the args
        $args = func_get_args();
        array_shift($args);

        // prepare for the transaction
        $this->result = null;
        $write_connections = $this->getWriteConnections($mapper_locator);

        // run the transaction
        try {

            $this->begin($write_connections);
            $this->result = call_user_func_array($queries, $args);
            $this->commit($write_connections);
            return true;

        } catch (Exception $e) {

            $this->result = $e;
            $this->rollback($write_connections);
            return false;

        }
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
     * Loads all write connections from the mappers.
     *
     * @return null
     *
     */
    protected function getWriteConnections(MapperLocator $mapper_locator)
    {
        $write_connections = new SplObjectStorage;
        foreach ($mapper_locator as $mapper) {
            $connection = $mapper->getWriteConnection();
            $write_connections->attach($connection);
        }
        return $write_connections;
    }

    /**
     *
     * Begins a transaction on all write connections.
     *
     * @param SplObjectStorage $write_connections The write connections from the
     * mappers in the mapper locator.
     *
     * @return null
     *
     */
    protected function begin(SplObjectStorage $write_connections)
    {
        foreach ($write_connections as $connection) {
            $connection->beginTransaction();
        }
    }

    /**
     *
     * Commits the transactions on all write connections.
     *
     * @param SplObjectStorage $write_connections The write connections from the
     * mappers in the mapper locator.
     *
     * @return null
     *
     */
    protected function commit(SplObjectStorage $write_connections)
    {
        foreach ($write_connections as $connection) {
            $connection->commit();
        }
    }

    /**
     *
     * Rolls back the transactions on all write connections.
     *
     * @param SplObjectStorage $write_connections The write connections from the
     * mappers in the mapper locator.
     *
     * @return null
     *
     */
    protected function rollback(SplObjectStorage $write_connections)
    {
        foreach ($write_connections as $connection) {
            $connection->rollBack();
        }
    }
}
