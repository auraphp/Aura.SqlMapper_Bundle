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

/**
 *
 * AbstractQuery class for Aura Sql
 *
 * @package Aura.SqlMapper_Bundle
 *
 */
abstract class AbstractQuery
{
    /**
     *
     * @var ExtendedPdo
     *
     */
    protected $connection;

    protected $query;

    /**
     *
     * Convert sql query object to string
     *
     * @return string
     *
     */
    public function __toString()
    {
        return $this->query->__toString();
    }

    /**
     *
     * Triggered when invoking inaccessible methods in an object context
     *
     * @return mixed
     *
     */
    public function __call($method, $params)
    {
        $result = call_user_func_array([$this->query, $method], $params);
        return ($result === $this->query) ? $this : $result;
    }
}
