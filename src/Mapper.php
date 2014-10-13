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

use Aura\SqlMapper_Bundle\Query\QueryFactory;
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
class Mapper
{
    /**
     *
     * The SQL table this mapper works with.
     *
     * @var string
     *
     */
    protected $table;

    /**
     *
     * A map of table columns to entity fields.
     *
     * @var array
     *
     */
    protected $cols_fields = [];

    /**
     *
     * The primary column in the table (maps to the identity field.)
     *
     * @var string
     *
     */
    protected $primary_col;

    /**
     *
     * The identity field in the entity (maps to the primary column).
     *
     * @var string
     *
     */
    protected $identity_field;

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
     * @param QueryFactory $query_factory A query factory.
     *
     * @param callable $entity_factory An entity factory.
     *
     * @param callable $collection_factory A collection factory.
     *
     */
    public function __construct(
        QueryFactory $query_factory,
        $entity_factory = null,
        $collection_factory = null
    ) {
        if (! $entity_factory) {
            $entity_factory = function (array $row) {
                return (object) $row;
            };
        }

        if (! $collection_factory) {
            $collection_factory = function (array $rows) use ($entity_factory) {
                $collection = array();
                foreach ($rows as $row) {
                    $collection[] = $entity_factory($row);
                }
                return $collection;
            };
        }

        $this->query_factory = $query_factory;
        $this->entity_factory = $entity_factory;
        $this->collection_factory = $collection_factory;
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
        return call_user_func($this->entity_factory, $select->fetchOne());
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
        return call_user_func($this->collection_factory, $select->fetchAll());
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
        $select = $this->query_factory->newSelect();
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
        if (! $cols) {
            // by default, select all cols
            $cols = $this->getCols();
        }

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
        $insert = $this->query_factory->newInsert();
        $this->modifyInsert($insert, $entity);
        $affected = $insert->perform();
        if ($affected) {
            $this->modifyInsertedEntity($insert, $entity);
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
        $data = $this->getInsertData($entity);
        $insert->into($this->table);
        $insert->cols(array_keys($data));
        $insert->bindValues($data);
    }

    /**
     *
     * Given an entity object, creates an array of mapped table column names
     * to entity field values for inserts.
     *
     * @param object $entity The entity object.
     *
     * @return array
     *
     */
    protected function getInsertData($entity)
    {
        $data = [];
        foreach ($this->cols_fields as $col => $field) {
            $data[$col] = $entity->$field;
        }
        return $data;
    }

    /**
     *
     * Modifes an entity after it was inserted.
     *
     * @param Insert $insert The Insert query object.
     *
     * @param object $entity The entity object.
     *
     * @return null
     *
     */
    protected function modifyInsertedEntity(Insert $insert, $entity)
    {
        $identity = $this->getIdentityField();
        $entity->$identity = $insert->fetchId($this->getPrimaryCol());
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
        $update = $this->query_factory->newUpdate();
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
        $data = $this->getUpdateData($entity, $initial_data);
        $update->table($this->getTable());
        $update->cols(array_keys($data));
        $update->where("{$this->primary_col} = :{$this->primary_col}");
        $update->bindValues($data);
        $update->bindValue($this->primary_col, $this->getIdentityValue($entity));
    }

    /**
     *
     * Given an entity object, creates an array of mapped table column names
     * to entity field values for updates; when an array of initial data is
     * present, the returned array will have only changed values.
     *
     * @param object $entity The entity object.
     *
     * @param array|object $initial_data The initial data for the entity.
     *
     * @return array
     *
     */
    protected function getUpdateData($entity, $initial_data = null)
    {
        if ($initial_data) {
            $data = $this->getUpdateDataChanges($entity, $initial_data);
        } else {
            $data = [];
            foreach ($this->cols_fields as $col => $field) {
                $data[$col] = $entity->$field;
            }
        }

        unset($data[$this->primary_col]);
        return $data;
    }

    /**
     *
     * Given an entity object and an array of initial data, returns an array
     * mapped table columns and changed values.
     *
     * @param object $entity The entity object.
     *
     * @param array $initial_data The array of initial data.
     *
     * @return array
     *
     */
    protected function getUpdateDataChanges($entity, $initial_data)
    {
        $initial_data = (object) $initial_data;
        $data = [];
        foreach ($this->cols_fields as $col => $field) {
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
        $delete = $this->query_factory->newDelete();
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
        $delete->from($this->table);
        $primary = $this->getPrimaryCol();
        $delete->where("{$primary} = :{$primary}");
        $delete->bindValue($primary, $this->getIdentityValue($entity));
    }

    /**
     *
     * Returns the list of table columns.
     *
     * @return array
     *
     */
    public function getCols()
    {
        return array_keys($this->cols_fields);
    }

    /**
     *
     * Returns the table column name for a given entity field name.
     *
     * @param string $field The entity field name.
     *
     * @return string The mapped table column name.
     *
     */
    public function getColForField($field)
    {
        return array_search($field, $this->cols_fields);
    }

    /**
     *
     * Returns the list of entity fields.
     *
     * @return array
     *
     */
    public function getFields()
    {
        return array_values($this->cols_fields);
    }

    /**
     *
     * Returns the entity field name for a given table column name.
     *
     * @param string $col The table column name.
     *
     * @return string The mapped entity field name.
     *
     */
    public function getFieldForCol($col)
    {
        return $this->cols_fields[$col];
    }

    /**
     *
     * Returns the identity field name for mapped entities.
     *
     * @return string The identity field name.
     *
     */
    public function getIdentityField()
    {
        return $this->identity_field;
    }

    /**
     *
     * Given an entity object, returns the identity field value.
     *
     * @param object $entity The entity object.
     *
     * @return mixed The value of the identity field on the object.
     *
     */
    public function getIdentityValue($entity)
    {
        $field = $this->identity_field;
        return $entity->$field;
    }

    /**
     *
     * Returns the primary column name on the table.
     *
     * @return string The primary column name.
     *
     */
    public function getPrimaryCol()
    {
        return $this->primary_col;
    }

    /**
     *
     * Returns the mapped SQL table name.
     *
     * @return string The mapped SQL table name.
     *
     */
    public function getTable()
    {
        return $this->table;
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
    public function getTableCol($col)
    {
        return $this->table . '.' . $col;
    }

    /**
     *
     * Returns a column name, dot-prefixed with the table name, "AS" its
     * mapped entity name.
     *
     * @param string $col The column name.
     *
     * @return string The fully-qualified table-and-column name "AS" the
     * mapped entity name.
     *
     */
    public function getTableColAsField($col)
    {
        return $this->getTableCol($col) . ' AS ' . $this->getFieldForCol($col);
    }

    /**
     *
     * Returns the primary column name, dot-prefixed with the table name.
     *
     * @return string The fully-qualified table-and-primary name.
     *
     */
    public function getTablePrimaryCol()
    {
        return $this->getTableCol($this->primary_col);
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
    public function getTableColsAsFields($cols)
    {
        $list = [];
        foreach ($cols as $col) {
            $list[] = $this->getTableColAsField($col);
        }
        return $list;
    }
}
