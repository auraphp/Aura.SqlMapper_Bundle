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
 * A RowDataGateway to a table.
 *
 * @package Aura.SqlMapper_Bundle
 *
 */
interface GatewayInterface
{
    /**
     *
     * Returns the gateway SQL table name.
     *
     * @return string The gateway SQL table name.
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
     * Is the primary key automatically set by the database at insert time,
     * (e.g., by autoincrement)?
     *
     * @return bool
     *
     */
    public function isAutoPrimary();

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
    public function selectBy($col, $val, array $cols = []);

    /**
     *
     * Returns a new Select query for the gateway table using a read
     * connection.
     *
     * @param array $cols Select these columns from the table; when empty,
     * selects all gateway columns.
     *
     * @return Select
     *
     */
    public function select(array $cols = []);

    /**
     *
     * Inserts a row array into the gateway table using a write connection.
     *
     * @param array $row The row array to insert.
     *
     * @return mixed
     *
     */
    public function insert(array $row);

    /**
     *
     * Updates a row in the table using a write connection.
     *
     * @param array $row The row array to update.
     *
     * @return bool True if the update succeeded, false if not.  (This is
     * determined by checking the number of rows affected by the query.)
     *
     */
    public function update(array $row);

    /**
     *
     * Deletes a row array from the gateway table using a write connection.
     *
     * @param array $row The row array to delete.
     *
     * @return bool True if the delete succeeded, false if not.  (This is
     * determined by checking the number of rows affected by the query.)
     *
     */
    public function delete(array $row);
}
