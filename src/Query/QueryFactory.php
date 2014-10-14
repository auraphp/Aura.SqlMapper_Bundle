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

use Aura\Sql\ConnectionLocator;
use Aura\SqlQuery\QueryFactory as UnderlyingQueryFactory;

/**
 *
 * Factory to create Select, Insert, Update, Delete objects
 *
 * @package Aura.SqlMapper_Bundle
 *
 */
class QueryFactory
{
    /**
     *
     * @param UnderlyingQueryFactory $query
     *
     * @param ConnectionLocator $connections
     *
     */
    public function __construct(
        UnderlyingQueryFactory $query,
        ConnectionLocator $connections
    ) {
        $this->query = $query;
        $this->connections = $connections;
    }

    /**
     *
     * @return Select
     *
     */
    public function newSelect()
    {
        return new Select(
            $this->query->newSelect(),
            $this->connections->getRead()
        );
    }

    /**
     *
     * @return Insert
     *
     */
    public function newInsert()
    {
        return new Insert(
            $this->query->newInsert(),
            $this->connections->getWrite()
        );
    }

    /**
     *
     * @return Update
     *
     */
    public function newUpdate()
    {
        return new Update(
            $this->query->newUpdate(),
            $this->connections->getWrite()
        );
    }

    /**
     *
     * @return Delete
     *
     */
    public function newDelete()
    {
        return new Delete(
            $this->query->newDelete(),
            $this->connections->getWrite()
        );
    }
}
