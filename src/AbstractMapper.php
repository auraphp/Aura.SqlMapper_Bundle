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

use Aura\Sql\ConnectionLocator;
use Aura\SqlMapper_Bundle\Query\ConnectedQueryFactory;
use Aura\SqlMapper_Bundle\Query\Select;
use Aura\SqlMapper_Bundle\Query\Insert;
use Aura\SqlMapper_Bundle\Query\Update;
use Aura\SqlMapper_Bundle\Query\Delete;

/**
 *
 * Maps database columns to individual object fields, and queries/modifies the
 * database.
 *
 * Note that Select results will return the field names, not the column names.
 *
 * @package Aura.SqlMapper_Bundle
 *
 */
abstract class AbstractMapper implements MapperInterface
{
    /**
     *
     * A database connection locator.
     *
     * @var ConnectionLocator
     *
     */
    protected $connection_locator;

    /**
     *
     * A factory to create query statements.
     *
     * @var QueryFactory
     *
     */
    protected $query_factory;

    /**
     *
     * A callable to create individual objects.
     *
     * @var callable
     *
     */
    protected $object_factory;

    /**
     *
     * A callable to create collections.
     *
     * @var callable
     *
     */
    protected $collection_factory;

    protected $insert_filter;

    protected $update_filter;

    protected $read_connection;

    protected $write_connection;

    /**
     *
     * Constructor.
     *
     * @param ConnectedQueryFactory $query_factory A query factory.
     *
     * @param callable $object_factory An individual object factory.
     *
     * @param callable $collection_factory A collection factory.
     *
     */
    public function __construct(
        ConnectionLocator $connection_locator,
        ConnectedQueryFactory $query_factory,
        $object_factory = null,
        $collection_factory = null,
        $insert_filter = null,
        $update_filter = null
    ) {
        $this->connection_locator = $connection_locator;
        $this->query_factory = $query_factory;

        if (! $object_factory) {
            $object_factory = function (array $row = array()) {
                return (object) $row;
            };
        }
        $this->object_factory = $object_factory;

        if (! $collection_factory) {
            $collection_factory = function (array $rows = array()) {
                $collection = array();
                foreach ($rows as $row) {
                    $collection[] = (object) $row;
                }
                return $collection;
            };
        }
        $this->collection_factory = $collection_factory;

        $this->insert_filter = $insert_filter;
        $this->update_filter = $update_filter;
    }

    /**
     *
     * Returns the mapped SQL table name.
     *
     * @return string The mapped SQL table name.
     *
     */
    abstract public function getTable();

    /**
     *
     * Returns the primary column name on the table.
     *
     * @return string The primary column name.
     *
     */
    abstract public function getPrimaryCol();

    /**
     *
     * Returns the map of column names to field names.
     *
     * @return array
     *
     */
    abstract public function getColsFields();

    /**
     *
     * Given an individual object, returns its identity field value.
     *
     * By default, this assumes a public property named for the primary column
     * (or one that appears public via the magic __get() method).
     *
     * If the individual object uses a different property name, or uses a method
     * instead, override this method to provide getter functionality.
     *
     * @param object $object The individual object.
     *
     * @return mixed The value of the identity field on the individual object.
     *
     */
    public function getIdentityValue($object)
    {
        $field = $this->getPrimaryCol();
        return $object->$field;
    }

    /**
     *
     * Given an individual object, sets its identity field value.
     *
     * By default, this assumes a public property named for the primary column
     * (or one that appears public via the magic __set() method).
     *
     * If the individual object uses a different property name, or uses a method
     * instead, override this method to provide setter functionality.
     *
     * @param object $object The individual object.
     *
     * @param mixed $value The identity field value to set.
     *
     * @return null
     *
     */
    public function setIdentityValue($object, $value)
    {
        $field = $this->getPrimaryCol();
        $object->$field = $value;
    }

    /**
     *
     * Returns the database read connection.
     *
     * @return ExtendedPdoInterface
     *
     */
    public function getReadConnection()
    {
        if (! $this->read_connection) {
            $this->read_connection = $this->connection_locator->getRead();
        }
        return $this->read_connection;
    }

    /**
     *
     * Returns the database write connection.
     *
     * @return ExtendedPdoInterface
     *
     */
    public function getWriteConnection()
    {
        if (! $this->write_connection) {
            $this->write_connection = $this->connection_locator->getWrite();
        }
        return $this->write_connection;
    }

    /**
     *
     * Returns an individual object from the Select results.
     *
     * @param Select $select Select statement for the individual object.
     *
     * @return mixed
     *
     */
    public function fetchObject(Select $select)
    {
        $row = $select->fetchOne();
        if ($row) {
            return $this->newObject($row);
        }
        return false;
    }

    /**
     *
     * Instantiates a new individual object from an array of field data.
     *
     * @param array $data Field data for the individual object.
     *
     * @return mixed
     *
     */
    public function newObject(array $data = array())
    {
        $factory = $this->object_factory;
        return $factory($data);
    }

    /**
     *
     * Returns an individual object from the mapped table for a given column and
     * value(s).
     *
     * @param string $col The column to use for matching.
     *
     * @param mixed $val The value(s) to match against; this can be an array
     * of values.
     *
     * @return array
     *
     */
    public function fetchObjectBy($col, $val)
    {
        return $this->fetchObject($this->selectBy($col, $val));
    }

    /**
     *
     * Returns a collection from the Select results.
     *
     * @param Select $select Select statement for the collection.
     *
     * @return mixed
     *
     */
    public function fetchCollection(Select $select)
    {
        $rows = $select->fetchAll();
        if ($rows) {
            return $this->newCollection($rows);
        }
        return array();
    }

    /**
     *
     * Instantiates a new collection from an array of field data arrays.
     *
     * @param array $data An array of field data arrays.
     *
     * @return mixed
     *
     */
    public function newCollection(array $data = array())
    {
        $factory = $this->collection_factory;
        return $factory($data);
    }

    /**
     *
     * Returns a collection from the mapped table for a given column and value.
     *
     * @param string $col The column to use for matching.
     *
     * @param mixed $val The value(s) to match against; this can be an array
     * of values.
     *
     * @return array
     *
     */
    public function fetchCollectionBy($col, $val)
    {
        return $this->fetchCollection($this->selectBy($col, $val));
    }

    /**
     *
     * Creates a Select query to match against a given column and value(s).
     *
     * @param string $col The column to use for matching.
     *
     * @param mixed $val The value(s) to match against; this can be an array
     * of values.
     *
     * @return Select
     *
     */
    public function selectBy($col, $val)
    {
        $select = $this->select();
        $where = $this->getTableCol($col);
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
     * Returns a new Select query for the mapped table using a read
     * connection.
     *
     * @param array $cols Select these columns from the table; when empty,
     * selects all mapped columns.
     *
     * @return Select
     *
     */
    public function select(array $cols = [])
    {
        $connection = $this->getReadConnection();
        $select = $this->query_factory->newSelect($connection);
        $this->modifySelect($select, $cols);
        return $select;
    }

    /**
     *
     * Given a Select query and an array of column names, modifies the Select
     * SELECT those columns AS their mapped field names FROM the mapped
     * table.
     *
     * @param Select $select The Select query to modify.
     *
     * @param array $cols The columns to select; if empty, selects all mapped
     * columns.
     *
     * @return null
     *
     */
    protected function modifySelect(Select $select, array $cols = [])
    {
        $select->from($this->getTable());
        $select->cols($this->getTableColsAsFields($cols));
    }

    /**
     *
     * Inserts an individual object into the mapped table using a write
     * connection.
     *
     * @param object $object The individual object to insert.
     *
     * @return int The number of affected rows.
     *
     */
    public function insert($object)
    {
        $this->filterForInsert($object);
        $insert = $this->newInsert($object);
        $affected = $insert->perform();
        if ($affected && $this->isAutoIdentity()) {
            $this->setAutoIdentity($insert, $object);
        }
        return $affected;
    }

    protected function filterForInsert($object)
    {
        if ($this->insert_filter) {
            $filter = $this->insert_filter;
            $filter($object);
        }
    }

    protected function newInsert($object)
    {
        $connection = $this->getWriteConnection();
        $insert = $this->query_factory->newInsert($connection);
        $this->modifyInsert($insert, $object);
        return $insert;
    }

    protected function isAutoIdentity()
    {
        return true;
    }

    protected function setAutoIdentity(Insert $insert, $object)
    {
        $this->setIdentityValue(
            $object,
            $insert->fetchId($this->getPrimaryCol())
        );
    }

    /**
     *
     * Given an Insert query and an individual object, modifies the Insert
     * to use the mapped table, with the column names mapped from the field
     * names, and binds the individual field values to the query.
     *
     * @param Insert $insert The Insert query.
     *
     * @param object $object The individual object.
     *
     * @return null
     *
     */
    protected function modifyInsert(Insert $insert, $object)
    {
        $data = $this->getInsertData($object);
        $insert->into($this->getTable());
        $insert->cols(array_keys($data));
        $insert->bindValues($data);
    }

    protected function getInsertData($object)
    {
        $data = $this->getObjectData($object);
        if ($this->isAutoIdentity()) {
            unset($data[$this->getPrimaryCol()]);
        }
        return $data;
    }

    /**
     *
     * Updates an individual object in the mapped table using a write
     * connection; if an array of initial data is present, updates only changed
     * values.
     *
     * @param object $object The individual object to update.
     *
     * @param array $initial_data Initial data for the individual object.
     *
     * @return bool True if the update succeeded, false if not.  (This is
     * determined by checking the number of rows affected by the query.)
     *
     */
    public function update($object, $initial_data = null)
    {
        $this->filterForUpdate($object);
        $update = $this->newUpdate($object, $initial_data);
        return $update->perform();
    }

    protected function filterForUpdate($object)
    {
        if ($this->update_filter) {
            $filter = $this->update_filter;
            $filter($object);
        }
    }

    protected function newUpdate($object, $initial_data)
    {
        $connection = $this->getWriteConnection();
        $update = $this->query_factory->newUpdate($connection);
        $this->modifyUpdate($update, $object, $initial_data);
        return $update;
    }

    /**
     *
     * Given an Update query and an individual object, modifies the Update
     * to use the mapped table, with the column names mapped from the
     * field names, binding the field values to the query, and setting
     * a where condition to match the primary column to the identity value.
     * When an array of initial data is present, the update will use only
     * changed values (instead of sending all the individual object values).
     *
     * @param Update $update The Update query.
     *
     * @param object $object The individual object.
     *
     * @param array $initial_data The initial data for the individual object;
     * used to determine what values have changed on the individual object.
     *
     * @return null
     *
     */
    protected function modifyUpdate(Update $update, $object, $initial_data = null)
    {
        $data = $this->getUpdateData($object, $initial_data);
        $primary_col = $this->getPrimaryCol();

        $update->table($this->getTable());
        $update->cols(array_keys($data));
        $update->where("{$primary_col} = :{$primary_col}");

        $update->bindValue($primary_col, $this->getIdentityValue($object));
        $update->bindValues($data);
    }

    protected function getUpdateData($object, $initial_data)
    {
        $data = $this->getObjectData($object, $initial_data);
        unset($data[$this->getPrimaryCol()]);
        return $data;
    }

    /**
     *
     * Deletes an individual object from the mapped table using a write
     * connection.
     *
     * @param object $object The individual object to delete.
     *
     * @return bool True if the delete succeeded, false if not.  (This is
     * determined by checking the number of rows affected by the query.)
     *
     */
    public function delete($object)
    {
        $connection = $this->getWriteConnection();
        $delete = $this->query_factory->newDelete($connection);
        $this->modifyDelete($delete, $object);
        return $delete->perform();
    }

    /**
     *
     * Given a Delete query and an individual object, modify the Delete
     * to use the mapped table, and to set a where condition to match the
     * primary column to the identity value.
     *
     * @param Delete $delete The Delete query.
     *
     * @param object $object The individual object.
     *
     * @return null
     *
     */
    protected function modifyDelete(Delete $delete, $object)
    {
        $delete->from($this->getTable());
        $primary_col = $this->getPrimaryCol();
        $delete->where("{$primary_col} = :{$primary_col}");
        $delete->bindValue($primary_col, $this->getIdentityValue($object));
    }

    /**
     *
     * Returns an array of fully-qualified table columns names "AS" their
     * mapped field names.
     *
     * @param array $cols The column names.
     *
     * @return array
     *
     */
    protected function getTableColsAsFields(array $cols = array())
    {
        $cols_fields = $this->getColsFields();
        if ($cols_fields && ! $cols) {
            $cols = array_keys($cols_fields);
        }

        $list = [];
        foreach ($cols as $col) {
            $list[] = $this->getTableCol($col) . ' AS ' . $cols_fields[$col];
        }

        return $list;
    }

    /**
     *
     * Returns a column name, dot-prefixed with the table name.
     *
     * @param string $col The column name.
     *
     * @return string The fully-qualified table-and-column name.
     *
     */
    protected function getTableCol($col)
    {
        return $this->getTable() . '.' . $col;
    }

    /**
     *
     * Given an individual object, creates an array of table column names mapped
     * to field values.
     *
     * @param object $object The individual object.
     *
     * @param array $initial_data The array of initial data.
     *
     * @return array
     *
     */
    protected function getObjectData($object, $initial_data = null)
    {
        if ($initial_data) {
            return $this->getObjectDataChanges($object, $initial_data);
        }

        $data = [];
        foreach ($this->getColsFields() as $col => $field) {
            $data[$col] = $object->$field;
        }
        return $data;
    }

    /**
     *
     * Given an individual object and an array of initial data, returns an array
     * of table columns mapped to field values, but only for those values
     * that have changed from the initial data.
     *
     * @param object $object The individual object.
     *
     * @param array $initial_data The array of initial data.
     *
     * @return array
     *
     */
    protected function getObjectDataChanges($object, $initial_data)
    {
        $initial_data = (object) $initial_data;
        $data = [];
        foreach ($this->getColsFields() as $col => $field) {
            $new = $object->$field;
            $old = $initial_data->$field;
            if (! $this->compare($new, $old)) {
                $data[$col] = $new;
            }
        }
        return $data;
    }

    /**
     *
     * Compares a new value and an old value to see if they are the same.
     * If they are both numeric, use loose (==) equality; otherwise, use
     * strict (===) equality.
     *
     * @param mixed $new The new value.
     *
     * @param mixed $old The old value.
     *
     * @return bool True if they are equal, false if not.
     *
     */
    protected function compare($new, $old)
    {
        $numeric = is_numeric($new) && is_numeric($old);
        if ($numeric) {
            // numeric, compare loosely
            return $new == $old;
        } else {
            // non-numeric, compare strictly
            return $new === $old;
        }
    }
}
