<?php
namespace Aura\SqlMapper_Bundle\Query;

use Aura\Sql\ConnectionLocator;
use Aura\SqlQuery\QueryFactory as UnderlyingQueryFactory;

class QueryFactory
{
    public function __construct(
        UnderlyingQueryFactory $query,
        ConnectionLocator $connections
    ) {
        $this->query = $query;
        $this->connections = $connections;
    }

    public function newSelect()
    {
        return new Select(
            $this->query->newSelect(),
            $this->connections->getRead()
        );
    }

    public function newInsert()
    {
        return new Insert(
            $this->query->newInsert(),
            $this->connections->getWrite()
        );
    }

    public function newUpdate()
    {
        return new Update(
            $this->query->newUpdate(),
            $this->connections->getWrite()
        );
    }

    public function newDelete()
    {
        return new Delete(
            $this->query->newDelete(),
            $this->connections->getWrite()
        );
    }
}
