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
 * An abstract decorator for Aura.SqlQuery query objects.
 *
 * @package Aura.SqlMapper_Bundle
 *
 */
abstract class AbstractQueryDecorator
{
    /**
     *
     * The underlying query object being decorated.
     *
     * @var mixed
     *
     */
    protected $query;

    /**
     *
     * Decorate the underlyingly query object's __toString() method so that
     * (string) casting works properly.
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
     * Forwards method calls to the underlying query object.
     *
     * @param string $method The call to the underlying query object.
     *
     * @param array $params Params for the method call.
     *
     * @return mixed If the call returned the underlying query object (a fluent
     * method call) return *this* object instead to emulate the fluency;
     * otherwise return the result as-is.
     *
     */
    public function __call($method, $params)
    {
        $result = call_user_func_array([$this->query, $method], $params);
        return ($result === $this->query) ? $this : $result;
    }
}
