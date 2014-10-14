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

use Aura\Sql\ExtendedPdo;
use Aura\SqlQuery\Common\InsertInterface;

/**
 *
 * An object to perform INSERT queries directly against the database.
 *
 * @package Aura.SqlMapper_Bundle
 *
 */
class Insert extends AbstractConnectedQuery
{
    /**
     *
     * @param InsertInterface $query
     *
     * @param ExtendedPdo $connection
     *
     */
    public function __construct(
        InsertInterface $query,
        ExtendedPdo $connection
    ) {
        $this->query = $query;
        $this->connection = $connection;
    }

    /**
     *
     * Execute the query and return the number of rows affected.
     *
     * @return int
     *
     */
    public function perform()
    {
        $stmt = $this->connection->perform(
            $this->query->__toString(),
            $this->query->getBindValues()
        );
        return $stmt->rowCount();
    }

    /**
     *
     * Returns the ID of the last inserted row or sequence value.
     *
     * @return string
     *
     */
    public function fetchId($col)
    {
        $name = $this->query->getLastInsertIdName($col);
        return $this->connection->lastInsertId($name);
    }
}
