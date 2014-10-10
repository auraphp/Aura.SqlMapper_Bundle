<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @package Aura.Sql
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\SqlMapper_Bundle;

use Aura\Sql\ConnectionLocator;
use Aura\SqlQuery\Common\Select;
use Aura\SqlQuery\Common\Insert;
use Aura\SqlQuery\Common\Update;
use Aura\SqlQuery\Common\Delete;
use Aura\SqlQuery\QueryFactory;

/**
 *
 * A TableDataGateway implementation.
 *
 * @package Aura.Sql
 *
 */
class Gateway
{
    /**
     *
     * A ConnectionLocator for database connections.
     *
     * @var ConnectionLocator
     *
     */
    protected $connections;

    /**
     *
     * A mapper between this table gateway and entities.
     *
     * @var AbstractMapper
     *
     */
    protected $mapper;

    protected $query;

    /**
     *
     * Constructor.
     *
     * @param ConnectionLocator $connections A ConnectionLocator for database
     * connections.
     *
     * @param AbstractMapper $mapper A table-to-entity mapper.
     *
     */
    public function __construct(
        ConnectionLocator $connections,
        QueryFactory $query,
        AbstractMapper $mapper
    ) {
        $this->connections = $connections;
        $this->query = $query;
        $this->mapper = $mapper;
    }

    /**
     *
     * Gets the connection locator.
     *
     * @return ConnectionLocator
     *
     */
    public function getConnections()
    {
        return $this->connections;
    }

    /**
     *
     * Gets the mapper.
     *
     * @return ConnectionLocator
     *
     */
    public function getMapper()
    {
        return $this->mapper;
    }

    /**
     *
     * Inserts an entity into the mapped table using a write connection.
     *
     * @param object $entity The entity to insert.
     *
     * @return int The last insert ID.
     *
     */
    public function insert($entity)
    {
        $connection = $this->connections->getWrite();
        $insert = $this->query->newInsert();
        $this->mapper->modifyInsert($insert, $entity);
        $connection->perform($insert->__toString(), $insert->getBindValues());
        return $connection->lastInsertId();
    }

    /**
     *
     * Updates an entity in the mapped table using a write connection; if an
     * array of initial data is present, updates only changed values.
     *
     * @param object $entity The entity to update.
     *
     * @param array $initial_data Initial data for the entity.
     *
     * @return bool True if the update succeeded, false if not.  (This is
     * determined by checking the number of rows affected by the query.)
     *
     */
    public function update($entity, $initial_data = null)
    {
        $connection = $this->connections->getWrite();
        $update = $this->query->newUpdate();
        $this->mapper->modifyUpdate($update, $entity, $initial_data);
        $stmt = $connection->perform($update->__toString(), $update->getBindValues());
        return (bool) $stmt->rowCount();
    }

    /**
     *
     * Deletes an entity from the mapped table using a write connection.
     *
     * @param object $entity The entity to delete.
     *
     * @return bool True if the delete succeeded, false if not.  (This is
     * determined by checking the number of rows affected by the query.)
     *
     */
    public function delete($entity)
    {
        $connection = $this->connections->getWrite();
        $delete = $this->query->newDelete();
        $this->mapper->modifyDelete($delete, $entity);
        $stmt = $connection->perform($delete->__toString(), $delete->getBindValues());
        return (bool) $stmt->rowCount();
    }

    /**
     *
     * Returns a new Select object for the mapped table using a read
     * connection.
     *
     * @param array $cols Select these columns from the table; when empty,
     * selects all mapped columns.
     *
     * @return Select
     *
     */
    public function newSelect(array $cols = [])
    {
        $connection = $this->connections->getRead();
        $select = $this->query->newSelect();
        $this->mapper->modifySelect($select, $cols);
        return $select;
    }

    /**
     *
     * Selects one row from the mapped table for a given column and value(s).
     *
     * @param string $col The column to use for matching.
     *
     * @param mixed $val The value(s) to match against; this can be an array
     * of values.
     *
     * @return array
     *
     */
    public function fetchOneBy($col, $val)
    {
        $select = $this->newSelectBy($col, $val);
        return $this->fetchOne($select);
    }

    /**
     *
     * Selects all rows from the mapped table for a given column and value.
     *
     * @param string $col The column to use for matching.
     *
     * @param mixed $val The value(s) to match against; this can be an array
     * of values.
     *
     * @return array
     *
     */
    public function fetchAllBy($col, $val)
    {
        $select = $this->newSelectBy($col, $val);
        return $this->fetchAll($select);
    }

    /**
     *
     * Creates a Select object to match against a given column and value(s).
     *
     * @param string $col The column to use for matching.
     *
     * @param mixed $val The value(s) to match against; this can be an array
     * of values.
     *
     * @return Select
     *
     */
    protected function newSelectBy($col, $val)
    {
        $select = $this->newSelect();
        $where = $this->getMapper()->getTableCol($col);
        if (is_array($val)) {
            $where .= ' IN (?)';
        } else {
            $where .= ' = ?';
        }
        $select->where($where, $val);
        return $select;
    }

    /**
     *
     * Given a Select, fetches all rows.
     *
     * @param Select $select The Select query object.
     *
     * @return array
     *
     * @see Connection\AbstractConnection::fetchAll()
     *
     */
    public function fetchAll(Select $select)
    {
        $connection = $this->connections->getRead();
        return $connection->fetchAll($select->__toString(), $select->getBindValues());
    }

    /**
     *
     * Given a Select, fetches the first column of all rows.
     *
     * @param Select $select The Select query object.
     *
     * @return array
     *
     * @see Connection\AbstractConnection::fetchCol()
     *
     */
    public function fetchCol(Select $select)
    {
        $connection = $this->connections->getRead();
        return $connection->fetchCol($select->__toString(), $select->getBindValues());
    }

    /**
     *
     * Given a Select, fetches the first row.
     *
     * @param Select $select The Select query object.
     *
     * @return array
     *
     * @see Connection\AbstractConnection::fetchOne()
     *
     */
    public function fetchOne(Select $select)
    {
        $connection = $this->connections->getRead();
        return $connection->fetchOne($select->__toString(), $select->getBindValues());
    }

    /**
     *
     * Given a Select, fetches an array of key-value pairs where the first
     * column is the key and the second column is the value.
     *
     * @param Select $select The Select query object.
     *
     * @return array
     *
     * @see Connection\AbstractConnection::fetchPairs()
     *
     */
    public function fetchPairs(Select $select)
    {
        $connection = $this->connections->getRead();
        return $connection->fetchPairs($select->__toString(), $select->getBindValues());
    }

    /**
     *
     * Given a Select, fetches the first column of the first row.
     *
     * @param Select $select The Select query object.
     *
     * @return mixed
     *
     * @see Connection\AbstractConnection::fetchValue()
     *
     */
    public function fetchValue(Select $select)
    {
        $connection = $this->connections->getRead();
        return $connection->fetchValue($select->__toString(), $select->getBindValues());
    }
}
