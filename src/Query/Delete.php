<?php
namespace Aura\SqlMapper_Bundle\Query;

use Aura\Sql\ExtendedPdo;
use Aura\SqlQuery\Common\DeleteInterface;

class Delete extends AbstractQuery
{
    public function __construct(
        DeleteInterface $query,
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
}
