<?php
namespace Aura\SqlMapper_Bundle\Query;

use Aura\Sql\ExtendedPdo;
use Aura\SqlQuery\Common\DeleteInterface;

class Delete
{
    public function __construct(
        DeleteInterface $delete,
        ExtendedPdo $connection
    ) {
        $this->delete = $delete;
        $this->connection = $connection;
    }

    public function __call($method, $params)
    {
        return call_user_func_array([$this->delete, $method], $params);
    }

    public function perform()
    {
        $stmt = $this->connection->perform(
            $this->delete->__toString(),
            $this->delete->getBindValues()
        );
        return $stmt->rowCount();
    }
}
