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
 * Interface for mapper objects.
 *
 * @package Aura.SqlMapper_Bundle
 *
 */
interface MapperInterface
{
    /**
     *
     * Returns the mapped SQL table name.
     *
     * @return string The mapped SQL table name.
     *
     */
    public function getTable();

    /**
     *
     * Returns the primary column name on the table.
     *
     * @return string The primary column name.
     *
     */
    public function getPrimaryCol();

    /**
     *
     * Returns the map of column names to field names.
     *
     * @return array
     *
     */
    public function getColsFields();

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
    public function getIdentityValue($object);

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
    public function setIdentityValue($object, $value);

    /**
     *
     * Returns the database read connection.
     *
     * @return ExtendedPdoInterface
     *
     */
    public function getReadConnection();

    /**
     *
     * Returns the database write connection.
     *
     * @return ExtendedPdoInterface
     *
     */
    public function getWriteConnection();

    /**
     *
     * Returns an individual object from the Select results.
     *
     * @param Select $select Select statement for the individual object.
     *
     * @return mixed
     *
     */
    public function fetchObject(Select $select);

    /**
     *
     * Instantiates a new individual object from an array of field data.
     *
     * @param array $data Field data for the individual object.
     *
     * @return mixed
     *
     */
    public function newObject(array $data = array());

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
    public function fetchObjectBy($col, $val);

    /**
     *
     * Returns a collection from the Select results.
     *
     * @param Select $select Select statement for the collection.
     *
     * @return mixed
     *
     */
    public function fetchCollection(Select $select);

    /**
     *
     * Instantiates a new collection from an array of field data arrays.
     *
     * @param array $data An array of field data arrays.
     *
     * @return mixed
     *
     */
    public function newCollection(array $data = array());

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
    public function fetchCollectionBy($col, $val);

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
    public function selectBy($col, $val);

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
    public function select(array $cols = []);

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
    public function insert($object);

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
    public function update($object, $initial_data = null);

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
    public function delete($object);
}
