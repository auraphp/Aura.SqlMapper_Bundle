<?php
namespace Aura\SqlMapper_Bundle\Query;

use Aura\Sql\ExtendedPdo;
use Aura\SqlQuery\Common\UpdateInterface;

class Update
{
    public function __construct(
        UpdateInterface $update,
        ExtendedPdo $connection
    ) {
        $this->update = $update;
        $this->connection = $connection;
    }

    public function __call($method, $params)
    {
        return call_user_func_array([$this->update, $method], $params);
    }

    public function perform()
    {
        $stmt = $this->connection->perform(
            $this->update->__toString(),
            $this->update->getBindValues()
        );
        return $stmt->rowCount();
    }
}
