<?php
namespace Aura\SqlMapper_Bundle\Query;

abstract class AbstractQuery
{
    protected $connection;

    protected $query;

    public function __toString()
    {
        return $this->query->__toString();
    }

    public function __call($method, $params)
    {
        $result = call_user_func_array([$this->query, $method], $params);
        return ($result === $this->query) ? $this : $result;
    }
}
