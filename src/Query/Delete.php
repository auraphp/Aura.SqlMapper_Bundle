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
use Aura\SqlQuery\Common\DeleteInterface;

/**
 *
 * An object to perform MySQL DELETE queries.
 *
 * @package Aura.SqlMapper_Bundle
 *
 */
class Delete extends AbstractQuery
{
    /**
     *
     * @param DeleteInterface $query
     *
     * @param ExtendedPdo $connection
     *
     */
    public function __construct(
        DeleteInterface $query,
        ExtendedPdo $connection
    ) {
        $this->query = $query;
        $this->connection = $connection;
    }

    /**
     *
     * Execute the SQL statement and returns the number of rows affected by the last SQL statement
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
}
