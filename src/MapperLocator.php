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
namespace Aura\SqlMapper_Bundle;

use IteratorAggregate;

/**
 *
 * A ServiceLocator implementation for loading and retaining mapper objects;
 * note that new mappers cannot be added after construction.
 *
 * @package Aura.SqlMapper_Bundle
 *
 */
class MapperLocator implements IteratorAggregate
{
    /**
     *
     * A registry of callable factories to create object instances.
     *
     * @var array
     *
     */
    protected $factories = [];

    /**
     *
     * A registry of object instances created by the factories.
     *
     * @var array
     *
     */
    protected $instances = [];

    /**
     *
     * Constructor.
     *
     * @param array $factories An array of key-value pairs where the key is a
     * name and the value is a callable that returns a mapper instance.
     *
     */
    public function __construct(array $factories = [])
    {
        $this->factories = $factories;
    }

    /**
     *
     * IteratorAggregate: Returns an iterator for this locator.
     *
     * @return MapperIterator
     *
     */
    public function getIterator()
    {
        return new MapperIterator($this, array_keys($this->factories));
    }

    /**
     *
     * Gets a mapper instance by name; if it has not been created yet, its
     * callable factory will be invoked and the instance will be retained.
     *
     * @param string $name The name of the mapper instance to retrieve.
     *
     * @return MapperInterface A mapper instance.
     *
     * @throws Exception\NoSuchMapper when an unrecognized mapper name is
     * given.
     *
     */
    public function __get($name)
    {
        if (! isset($this->factories[$name])) {
            throw new Exception\NoSuchMapper($name);
        }

        if (! isset($this->instances[$name])) {
            $callable = $this->factories[$name];
            $this->instances[$name] = call_user_func($callable);
        }

        return $this->instances[$name];
    }
}
