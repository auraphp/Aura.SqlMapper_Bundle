<?php
namespace Aura\SqlMapper_Bundle\Query;

use Aura\Sql\ExtendedPdo;
use Aura\SqlQuery\Common\InsertInterface;

class Insert
{
    public function __construct(
        ExtendedPdo $connection,
        InsertInterface $insert
    ) {
        $this->insert = $insert;
        $this->connection = $connection;
    }

    public function __call($method, $params)
    {
        return call_user_func_array([$this->insert, $method], $params);
    }

    public function perform()
    {
        $stmt = $this->connection->perform(
            $this->insert->__toString(),
            $this->insert->getBindValues()
        );
        return $stmt->rowCount();
    }

    public function fetchId($col)
    {
        $name = $this->insert->getLastInsertIdName($col);
        return $this->connection->lastInsertId($name);
    }
}
