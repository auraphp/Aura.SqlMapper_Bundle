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
 * A ServiceLocator implementation for loading and retaining mapper objects.
 *
 * @package Aura.SqlMapper_Bundle
 *
 */
class MapperLocator implements IteratorAggregate
{
    /**
     *
     * A registry to retain mapper objects.
     *
     * @var array
     *
     */
    protected $registry;

    /**
     *
     * Tracks whether or not a registry entry has been converted from a
     * callable to a mapper object.
     *
     * @var array
     *
     */
    protected $converted = [];

    /**
     *
     * Constructor.
     *
     * @param array $registry An array of key-value pairs where the key is the
     * mapper name and the value is a callable that returns a mapper object.
     *
     */
    public function __construct(array $registry = [])
    {
        foreach ($registry as $name => $spec) {
            $this->set($name, $spec);
        }
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
        return new MapperIterator($this, array_keys($this->registry));
    }

    /**
     *
     * Sets a mapper into the registry by name.
     *
     * @param string $name The mapper name.
     *
     * @param callable $spec A callable that returns a mapper object.
     *
     * @return null
     *
     */
    public function set($name, callable $spec)
    {
        $this->registry[$name] = $spec;
        $this->converted[$name] = false;
    }

    /**
     *
     * Gets a mapper from the registry by name.
     *
     * @param string $name The mapper to retrieve.
     *
     * @return AbstractMapper A mapper object.
     *
     */
    public function get($name)
    {
        if (! isset($this->registry[$name])) {
            throw new Exception\NoSuchMapper($name);
        }

        if (! $this->converted[$name]) {
            $func = $this->registry[$name];
            $this->registry[$name] = $func();
            $this->converted[$name] = true;
        }

        return $this->registry[$name];
    }
}
