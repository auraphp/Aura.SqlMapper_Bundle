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
 * Maps database columns to entity fields, and queries/modifies the database.
 *
 * Note that Select results will return the field names, not the column names.
 *
 * @package Aura.SqlMapper_Bundle
 *
 */
abstract class AbstractMapper
{
    /**
     *
     * A database connetion locator.
     *
     * @var ConnectionLocator
     *
     */
    protected $connection_locator;

    /**
     *
     * A factory to create query objects.
     *
     * @var QueryFactory
     *
     */
    protected $query_factory;

    /**
     *
     * A callable to create entity objects.
     *
     * @var callable
     *
     */
    protected $entity_factory;

    /**
     *
     * A callable to create collection objects.
     *
     * @var callable
     *
     */
    protected $collection_factory;

    /**
     *
     * Constructor.
     *
     * @param ConnectedQueryFactory $query_factory A query factory.
     *
     * @param callable $entity_factory An entity factory.
     *
     * @param callable $collection_factory A collection factory.
     *
     */
    public function __construct(
        ConnectionLocator $connection_locator,
        ConnectedQueryFactory $query_factory
    ) {
        $this->connection_locator = $connection_locator;
        $this->query_factory = $query_factory;

        $this->setEntityFactory(function (array $row = array()) {
            return (object) $row;
        });

        $this->setCollectionFactory(function (array $rows = array()) {
            $collection = array();
            foreach ($rows as $row) {
                $collection[] = (object) $row;
            }
            return $collection;
        });
    }

    public function setEntityFactory($entity_factory)
    {
        $this->entity_factory = $entity_factory;
    }

    public function setCollectionFactory($collection_factory)
    {
        $this->collection_factory = $collection_factory;
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
     * By default this is empty, meaning that the column names map exactly
     * to the field names. This may be a good starting point, but eventually
     * you will want to specify the col => field mappings explicitly.
     *
     * @return array
     *
     */
    public function getColsFields()
    {
        return array();
    }

    /**
     *
     * Given an entity object, returns its identity field value.
     *
     * By default, this assumes a public property named for the primary column
     * (or one that appears public via the magic __get() method).
     *
     * If the entity uses a different property name, or uses a method instead,
     * override this method to provide getter functionality.
     *
     * @param object $entity The entity object.
     *
     * @return mixed The value of the identity field on the object.
     *
     */
    public function getIdentityValue($entity)
    {
        $field = $this->getPrimaryCol();
        return $entity->$field;
    }

    /**
     *
     * Given an entity object, sets its identity field value.
     *
     * By default, this assumes a public property named for the primary column
     * (or one that appears public via the magic __set() method).
     *
     * If the entity uses a different property name, or uses a method instead,
     * override this method to provide setter functionality.
     *
     * @param object $entity The entity object.
     *
     * @param mixed $value The identity field value to set on the object.
     *
     * @return null
     *
     */
    public function setIdentityValue($entity, $value)
    {
        $field = $this->getPrimaryCol();
        $entity->$field = $value;
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
        return $this->connection_locator->getRead();
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
        return $this->connection_locator->getWrite();
    }

    /**
     *
     * Returns an entity from the Select results.
     *
     * @param Select $select Select statement for the entity.
     *
     * @return mixed
     *
     */
    public function fetchEntity(Select $select)
    {
        $data = $select->fetchOne();
        if ($data) {
            return $this->newEntity($data);
        }
        return false;
    }

    /**
     *
     * Instantiates a new entity from an array of field data.
     *
     * @param array $data Field data for the entity.
     *
     * @return mixed
     *
     */
    public function newEntity(array $data = array())
    {
        return call_user_func($this->entity_factory, $data);
    }

    /**
     *
     * Returns an entity from the mapped table for a given column and value(s).
     *
     * @param string $col The column to use for matching.
     *
     * @param mixed $val The value(s) to match against; this can be an array
     * of values.
     *
     * @return array
     *
     */
    public function fetchEntityBy($col, $val)
    {
        $row = $this->selectBy($col, $val)->fetchOne();
        return call_user_func($this->entity_factory, $row);
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
        return $this->newCollection($select->fetchAll());
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
        return call_user_func($this->collection_factory, $data);
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
        $rows = $this->selectBy($col, $val)->fetchAll();
        return call_user_func($this->collection_factory, $rows);
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
     * Returns a new Select object for the mapped table using a read
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
     * Given a Select object and an array of column names, modifies the Select
     * SELECT those columns AS their mapped entity field names FROM the mapped
     * table.
     *
     * @param Select $select The Select object to modify.
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
     * Inserts an entity into the mapped table using a write connection.
     *
     * @param object $entity The entity to insert.
     *
     * @return int The last insert ID.
     *
     */
    public function insert($entity)
    {
        $connection = $this->getWriteConnection();
        $insert = $this->query_factory->newInsert($connection);
        $this->modifyInsert($insert, $entity);
        $affected = $insert->perform();
        if ($affected) {
            $this->setIdentityValue(
                $entity,
                $insert->fetchId($this->getPrimaryCol())
            );
        }
        return $affected;
    }

    /**
     *
     * Given an Insert query object and an entity object, modifies the Insert
     * to use the mapped table, with the column names mapped from the entity
     * field names, and binds the entity field values to the query.
     *
     * @param Insert $insert The Insert query object.
     *
     * @param object $entity The entity object.
     *
     * @return null
     *
     */
    protected function modifyInsert(Insert $insert, $entity)
    {
        $data = $this->getEntityData($entity);
        $insert->into($this->getTable());
        $insert->cols(array_keys($data));
        $insert->bindValues($data);
    }

    /**
     *
     * Given an entity object, creates an array of table column names mapped
     * to entity field values.
     *
     * @param object $entity The entity object.
     *
     * @param array $initial_data The array of initial data.
     *
     * @return array
     *
     */
    protected function getEntityData($entity, $initial_data = null)
    {
        if ($initial_data) {
            return $this->getEntityDataChanges($entity, $initial_data);
        }

        $data = [];
        foreach ($this->getColsFields() as $col => $field) {
            $data[$col] = $entity->$field;
        }
        return $data;
    }

    /**
     *
     * Given an entity object and an array of initial data, returns an array
     * of table columns mapped to entity values, but only for those values
     * that have changed from the initial data.
     *
     * @param object $entity The entity object.
     *
     * @param array $initial_data The array of initial data.
     *
     * @return array
     *
     */
    protected function getEntityDataChanges($entity, $initial_data)
    {
        $initial_data = (object) $initial_data;
        $data = [];
        foreach ($this->getColsFields() as $col => $field) {
            $new = $entity->$field;
            $old = $initial_data->$field;
            if (! $this->compare($new, $old)) {
                $data[$col] = $new;
            }
        }
        return $data;
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
        $connection = $this->getWriteConnection();
        $update = $this->query_factory->newUpdate($connection);
        $this->modifyUpdate($update, $entity, $initial_data);
        return $update->perform();
    }

    /**
     *
     * Given an Update query object and an entity object, modifies the Update
     * to use the mapped table, with the column names mapped from the entity
     * field names, binding the entity field values to the query, and setting
     * a where condition to match the primary column to the identity value.
     * When an array of initial data is present, the update will use only
     * changed values (instead of sending all the entity values).
     *
     * @param Update $update The Update query object.
     *
     * @param object $entity The entity object.
     *
     * @param array $initial_data The initial data for the entity object; used
     * to determine what values have changed on the entity.
     *
     * @return null
     *
     */
    protected function modifyUpdate(Update $update, $entity, $initial_data = null)
    {
        $data = $this->getEntityData($entity, $initial_data);
        $primary_col = $this->getPrimaryCol();
        unset($data[$primary_col]);

        $update->table($this->getTable());
        $update->cols(array_keys($data));
        $update->where("{$primary_col} = :{$primary_col}");

        $update->bindValue($primary_col, $this->getIdentityValue($entity));
        $update->bindValues($data);
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
        $connection = $this->getWriteConnection();
        $delete = $this->query_factory->newDelete($connection);
        $this->modifyDelete($delete, $entity);
        return $delete->perform();
    }

    /**
     *
     * Given a Delete query object and an entity object, modify the Delete
     * to use the mapped table, and to set a where condition to match the
     * primary column to the identity value.
     *
     * @param Delete $delete The Delete query object.
     *
     * @param object $entity The entity object.
     *
     * @return null
     *
     */
    protected function modifyDelete(Delete $delete, $entity)
    {
        $delete->from($this->getTable());
        $primary_col = $this->getPrimaryCol();
        $delete->where("{$primary_col} = :{$primary_col}");
        $delete->bindValue($primary_col, $this->getIdentityValue($entity));
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
     * Returns an array of fully-qualified table columns names "AS" their
     * mapped entity field names.
     *
     * @param array $cols The column names.
     *
     * @return array
     *
     */
    protected function getTableColsAsFields(array $cols = array())
    {
        $list = [];
        $cols_fields = $this->getColsFields();

        if (! $cols_fields && ! $cols) {
            $list[] = '*';
            return $list;
        }

        if ($cols && ! $cols_fields) {
            foreach ($cols as $col) {
                $list[] = $this->getTableCol($col);
            }
            return $list;
        }

        if ($cols_fields && ! $cols) {
            $cols = array_keys($cols_fields);
        }

        foreach ($cols as $col) {
            $list[] = $this->getTableCol($col) . ' AS ' . $cols_fields[$col];
        }

        return $list;
    }
}
