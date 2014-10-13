<?php
namespace Aura\SqlMapper_Bundle\Query;

use Aura\Sql\ExtendedPdo;
use Aura\SqlQuery\Common\InsertInterface;

class Insert extends AbstractQuery
{
    public function __construct(
        InsertInterface $query,
        ExtendedPdo $connection
    ) {
        $this->query = $query;
        $this->connection = $connection;
    }

    public function perform()
    {
        $stmt = $this->connection->perform(
            $this->query->__toString(),
            $this->query->getBindValues()
        );
        return $stmt->rowCount();
    }

    public function fetchId($col)
    {
        $name = $this->query->getLastInsertIdName($col);
        return $this->connection->lastInsertId($name);
    }
}
