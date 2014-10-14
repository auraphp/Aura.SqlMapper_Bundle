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
use Aura\SqlQuery\QueryFactory;

/**
 *
 * Factory to create query objects decorated with database connections.
 *
 * @package Aura.SqlMapper_Bundle
 *
 */
class ConnectedQueryFactory
{
    /**
     *
     * @param QueryFactory $query
     *
     * @param ConnectionLocator $connections
     *
     */
    public function __construct(QueryFactory $query)
    {
        $this->query = $query;
    }

    /**
     *
     * @return Select
     *
     */
    public function newSelect(ExtendedPdoInterface $connection)
    {
        return new Select($this->query->newSelect(), $connection);
    }

    /**
     *
     * @return Insert
     *
     */
    public function newInsert(ExtendedPdoInterface $connection)
    {
        return new Insert($this->query->newInsert(), $connection);
    }

    /**
     *
     * @return Update
     *
     */
    public function newUpdate(ExtendedPdoInterface $connection)
    {
        return new Update($this->query->newUpdate(), $connection);
    }

    /**
     *
     * @return Delete
     *
     */
    public function newDelete(ExtendedPdoInterface $connection)
    {
        return new Delete($this->query->newDelete(), $connection);
    }
}
