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
use Aura\SqlQuery\Common\DeleteInterface;

/**
 *
 * An object to perform DELETE queries directly against the database.
 *
 * @package Aura.SqlMapper_Bundle
 *
 */
class Delete extends AbstractConnectedQuery
{
    /**
     *
     * @param DeleteInterface $query
     *
     * @param ExtendedPdoInterface $connection
     *
     */
    public function __construct(
        DeleteInterface $query,
        ExtendedPdoInterface $connection
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
        $string = $this->query->getStatement();
        $values = $this->query->getBindValues();
        $stmt = $this->connection->perform($string, $values);
        return $stmt->rowCount();
    }
}
