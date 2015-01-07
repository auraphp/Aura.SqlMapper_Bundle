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

use Aura\SqlMapper_Bundle\Query\Select;

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
     * A callable to create individual objects.
     *
     * @var callable
     *
     */
    protected $object_factory;

    /**
     *
     * A callable to create object collections.
     *
     * @var callable
     *
     */
    protected $collection_factory;

    /**
     *
     * A filter for inserts and updates.
     *
     * @var FilterInterface
     *
     */
    protected $filter;

    /**
     *
     * A row data gateway.
     *
     * @var GatewayInterface
     *
     */
    protected $gateway;

    /**
     *
     * Constructor.
     *
     * @param GatewayInterface $gateway A row data gateway.
     *
     * @param ObjectFactoryInterface $object_factory An object factory.
     *
     * @param FilterInterface $filter A filter for inserts and updates.
     *
     */
    public function __construct(
        GatewayInterface $gateway,
        ObjectFactoryInterface $object_factory,
        FilterInterface $filter
    ) {
        $this->gateway = $gateway;
        $this->object_factory = $object_factory;
        $this->filter = $filter;
    }

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
     * Returns the name of the identity field on the object.
     *
     * @return array
     *
     */
    public function getIdentityField()
    {
        return $this->gateway->getPrimaryCol();
    }

    /**
     *
     * Given an individual object, returns its identity field value.
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
        $field = $this->getIdentityField();
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
        $field = $this->getIdentityField();
        $object->$field = $value;
    }

    /**
     *
     * Returns the underlying gateway read connection.
     *
     * @return ExtendedPdoInterface
     *
     */
    public function getReadConnection()
    {
        return $this->gateway->getReadConnection();
    }

    /**
     *
     * Returns the underlying gateway write connection.
     *
     * @return ExtendedPdoInterface
     *
     */
    public function getWriteConnection()
    {
        return $this->gateway->getWriteConnection();
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
        $row = $this->gateway->fetchRow($select);
        if ($row) {
            return $this->newObject($row);
        }
        return false;
    }

    /**
     *
     * Instantiates a new individual object from an array of field data.
     *
     * @param array $row Row data for the individual object.
     *
     * @return mixed
     *
     */
    public function newObject(array $row = array())
    {
        return $this->object_factory->newObject($row);
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
        $rows = $this->gateway->fetchRows($select);
        if ($rows) {
            return $this->newCollection($rows);
        }
        return array();
    }

    /**
     *
     * Instantiates a new collection from an array of row data arrays.
     *
     * @param array $rows An array of row data arrays.
     *
     * @return mixed
     *
     */
    public function newCollection(array $rows = array())
    {
        return $this->object_factory->newCollection($rows);
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
        $cols = $this->getColsAsFields();
        return $this->gateway->selectBy($col, $val, $cols);
    }

    /**
     *
     * Returns a new Select query for the mapped table using a read
     * connection.
     *
     * @return Select
     *
     */
    public function select()
    {
        $cols = $this->getColsAsFields();
        return $this->gateway->select($cols);
    }

    /**
     *
     * Inserts an individual object into the mapped table using a write
     * connection.
     *
     * @param object $object The individual object to insert.
     *
     * @return bool
     *
     */
    public function insert($object)
    {
        $this->filter->forInsert($object);

        $data = $this->getRowData($object);
        $row = $this->gateway->insert($data);
        if (! $row) {
            return false;
        }

        if ($this->gateway->isAutoPrimary()) {
            $this->setIdentityValue(
                $object,
                $this->gateway->getPrimaryVal($row)
            );
        }

        return true;
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
        $this->filter->forUpdate($object);
        $data = $this->getRowData($object, $initial_data);
        return (bool) $this->gateway->update($data);
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
        $row = $this->getRowData($object);
        return (bool) $this->gateway->delete($row);
    }

    /**
     *
     * Returns an array of columns "AS" their mapped field names.
     *
     * @param array $cols The column names.
     *
     * @return array
     *
     */
    protected function getColsAsFields(array $cols = array())
    {
        $cols_fields = $this->getColsFields();

        if ($cols_fields && ! $cols) {
            $cols = array_keys($cols_fields);
        }

        $list = [];
        foreach ($cols as $col) {
            $list[] = "{$col} AS {$cols_fields[$col]}";
        }

        return $list;
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
    protected function getRowData($object, $initial_data = null)
    {
        if ($initial_data) {
            return $this->getRowDataChanges($object, $initial_data);
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
    protected function getRowDataChanges($object, $initial_data)
    {
        $initial_data = (object) $initial_data;

        // always retain the primary identity
        $primary_col = $this->gateway->getPrimaryCol();
        $identity_value = $this->getIdentityValue($object);
        $data = array($primary_col => $identity_value);

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
