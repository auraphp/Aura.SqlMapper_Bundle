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
abstract class AbstractGateway implements GatewayInterface
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
     * A filter for inserts and updates.
     *
     * @var FilterInterface
     *
     */
    protected $filter;

    /**
     *
     * A read connection drawn from the connection locator.
     *
     * @var ExtendedPdoInterface
     *
     */
    protected $read_connection;

    /**
     *
     * A write connection drawn from the connection locator.
     *
     * @var ExtendedPdoInterface
     *
     */
    protected $write_connection;

    /**
     *
     * Constructor.
     *
     * @param ConnectionLocator $connection_locator A connection locator.
     *
     * @param ConnectedQueryFactory $query_factory A query factory.
     *
     * @param FilterInterface $filter A filter for inserts and updates.
     *
     */
    public function __construct(
        ConnectionLocator $connection_locator,
        ConnectedQueryFactory $query_factory,
        FilterInterface $filter
    ) {
        $this->connection_locator = $connection_locator;
        $this->query_factory = $query_factory;
        $this->filter = $filter;
    }

    /**
     *
     * Returns the gateway SQL table name.
     *
     * @return string The gateway SQL table name.
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
     * Does the database set the primary key value on insert, e.g. by using
     * auto-increment?
     *
     * @return bool
     *
     */
    public function isAutoPrimary()
    {
        return true;
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

    public function fetchRow(Select $select)
    {
        return $select->fetchOne();
    }

    public function fetchRowBy($col, $val, array $cols = [])
    {
        return $this->selectBy($col, $val, $cols)->fetchOne();
    }

    public function fetchRows(Select $select)
    {
        return $select->fetchAll();
    }

    public function fetchRowsBy($col, $val, array $cols = [])
    {
        return $this->selectBy($col, $val, $cols)->fetchAll();
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
    public function selectBy($col, $val, array $cols = [])
    {
        $select = $this->select($cols);
        $where = $this->getTableCol($col);
        if (is_array($val)) {
            $where .= " IN (:{$col})";
        } else {
            $where .= " = :{$col}";
        }
        $select->where($where);
        $select->bindValue($col, $val);
        return $select;
    }

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
    public function select(array $cols = [])
    {
        $select = $this->query_factory->newSelect($this->getReadConnection());
        $select->from($this->getTable());
        $select->cols($this->getTableCols($cols));
        return $select;
    }

    /**
     *
     * Inserts a row array into the gateway table using a write connection.
     *
     * @param array $row The row array to insert.
     *
     * @return mixed
     *
     */
    public function insert(array $row)
    {
        $row = $this->filter->forInsert($row);
        $insert = $this->newInsert($row);
        if (! $insert->perform()) {
            return false;
        }
        return $this->setAutoPrimary($insert, $row);
    }

    protected function newInsert(array $row)
    {
        if ($this->isAutoPrimary()) {
            unset($row[$this->getPrimaryCol()]);
        }

        $insert = $this->query_factory->newInsert($this->getWriteConnection());
        $insert->into($this->getTable());
        $insert->cols(array_keys($row));
        $insert->bindValues($row);

        return $insert;
    }

    protected function setAutoPrimary(Insert $insert, array $row)
    {
        if (! $this->isAutoPrimary()) {
            return $row;
        }

        return $this->setPrimaryVal(
            $row,
            $insert->fetchId($this->getPrimaryCol())
        );
    }

    public function getPrimaryVal(array $row)
    {
        return $row[$this->getPrimaryCol()];
    }

    protected function setPrimaryVal(array $row, $val)
    {
        $row[$this->getPrimaryCol()] = $val;
        return $row;
    }

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
    public function update(array $row)
    {
        $row = $this->filter->forUpdate($row);
        $update = $this->newUpdate($row);
        if (! $update->perform()) {
            return false;
        }
        return $row;
    }

    protected function newUpdate(array $row)
    {
        $primary_col = $this->getPrimaryCol();
        $primary_val = $this->getPrimaryVal($row);
        unset($row[$primary_col]);

        $update = $this->query_factory->newUpdate($this->getWriteConnection());
        $update->table($this->getTable());
        $update->cols($row);
        $update->where("{$primary_col} = :{$primary_col}");
        $update->bindValue($primary_col, $primary_val);

        return $update;
    }

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
    public function delete(array $row)
    {
        $primary_col = $this->getPrimaryCol();
        $primary_val = $this->getPrimaryVal($row);

        $delete = $this->query_factory->newDelete($this->getWriteConnection());
        $delete->from($this->getTable());
        $delete->where("{$primary_col} = :{$primary_col}");
        $delete->bindValue($primary_col, $primary_val);

        return (bool) $delete->perform();
    }

    /**
     *
     * Returns an array of fully-qualified table columns.
     *
     * @param array $cols The column names.
     *
     * @return array
     *
     */
    protected function getTableCols(array $cols = array())
    {
        if (! $cols) {
            $cols = array('*');
        }

        $list = [];
        foreach ($cols as $col) {
            $list[] = $this->getTableCol($col);
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
}
