<?php
/**
 *
 * This file is part of the Aura Project for PHP.
 *
 * @package Aura.SqlMapper_Bundle
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\SqlMapper_Bundle\Query;

use Aura\Sql\ExtendedPdoInterface;
use Aura\SqlQuery\Common\SelectInterface;

/**
 *
 * An object to perform SELECT queries directly against the database.
 *
 * @package Aura.SqlMapper_Bundle
 *
 */
class Select extends AbstractConnectedQuery
{
    /**
     *
     * @param SelectInterface $query
     *
     * @param ExtendedPdoInterface $connection
     *
     */
    public function __construct(
        SelectInterface $query,
        ExtendedPdoInterface $connection
    ) {
        $this->query = $query;
        $this->connection = $connection;
    }

    /**
     *
     * Fetches a sequential array of rows from the database; the rows
     * are represented as associative arrays.
     *
     * @return array
     *
     */
    public function fetchAll()
    {
        return $this->connection->fetchAll(
            $this->query->__toString(),
            $this->query->getBindValues()
        );
    }

    /**
     *
     * Fetches an associative array of rows from the database; the rows
     * are represented as associative arrays. The array of rows is keyed
     * on the first column of each row.
     *
     * N.b.: if multiple rows have the same first column value, the last
     * row with that value will override earlier rows.
     *
     * @return array
     *
     */
    public function fetchAssoc()
    {
        return $this->connection->fetchAssoc(
            $this->query->__toString(),
            $this->query->getBindValues()
        );
    }

    /**
     *
     * Fetches the first column of rows as a sequential array.
     *
     * @return array
     *
     */
    public function fetchCol()
    {
        return $this->connection->fetchCol(
            $this->query->__toString(),
            $this->query->getBindValues()
        );
    }

    /**
     *
     * Fetches one row from the database as an object, mapping column values
     * to object properties.
     *
     * Warning: PDO "injects property-values BEFORE invoking the constructor -
     * in other words, if your class initializes property-values to defaults
     * in the constructor, you will be overwriting the values injected by
     * fetchObject() !"
     * <http://www.php.net/manual/en/connectionstatement.fetchobject.php#111744>
     *
     * @param string $class_name The name of the class to create.
     *
     * @param array $ctor_args Arguments to pass to the object constructor.
     *
     * @return object
     *
     */
    public function fetchObject(
        $class_name = 'StdClass',
        array $ctor_args = array()
    ) {
        return $this->connection->fetchObject(
            $this->query->__toString(),
            $this->query->getBindValues(),
            $class_name,
            $ctor_args
        );
    }

    /**
     *
     * Fetches a sequential array of rows from the database; the rows
     * are represented as objects, where the column values are mapped to
     * object properties.
     *
     * Warning: PDO "injects property-values BEFORE invoking the constructor -
     * in other words, if your class initializes property-values to defaults
     * in the constructor, you will be overwriting the values injected by
     * fetchObject() !"
     * <http://www.php.net/manual/en/connectionstatement.fetchobject.php#111744>
     *
     * @param string $class_name The name of the class to create from each
     * row.
     *
     * @param array $ctor_args Arguments to pass to each object constructor.
     *
     * @return array
     *
     */
    public function fetchObjects(
        $class_name = 'StdClass',
        array $ctor_args = array()
    ) {
        return $this->connection->fetchObjects(
            $this->query->__toString(),
            $this->query->getBindValues(),
            $class_name,
            $ctor_args
        );
    }


    /**
     *
     * Fetches one row from the database as an associative array.
     *
     * @return array
     *
     */
    public function fetchOne()
    {
        return $this->connection->fetchOne(
            $this->query->__toString(),
            $this->query->getBindValues()
        );
    }

    /**
     *
     * Fetches an associative array of rows as key-value pairs (first
     * column is the key, second column is the value).
     *
     * @param array $values Values to bind to the query.
     *
     * @return array
     *
     */
    public function fetchPairs()
    {
        return $this->connection->fetchPairs(
            $this->query->__toString(),
            $this->query->getBindValues()
        );
    }

    /**
     *
     * Fetches the very first value (i.e., first column of the first row).
     *
     * @return mixed
     *
     */
    public function fetchValue()
    {
        return $this->connection->fetchValue(
            $this->query->__toString(),
            $this->query->getBindValues()
        );
    }
}
