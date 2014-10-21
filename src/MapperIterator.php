<?php
/**
 *
 * This file is part of the Aura project for PHP.
 *
 * @package Aura.SqlMapper_Bundle
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\SqlMapper_Bundle;

use Iterator;

/**
 *
 * An object to allow iteration over the elements of a MapperLocator.
 *
 * @package Aura.SqlMapper_Bundle
 *
 */
class MapperIterator implements Iterator
{
    /**
     *
     * The mappers over which we are iterating.
     *
     * @var MapperLocator
     *
     */
    protected $mappers;

    /**
     *
     * The keys to iterate over in the MapperLocator object.
     *
     * @var array
     *
     */
    protected $keys;

    /**
     *
     * Is the current iterator position valid?
     *
     * @var bool
     *
     */
    protected $valid;

    /**
     *
     * Constructor.
     *
     * @param MapperLocator $mappers The MapperLocator object over which to iterate.
     *
     * @param array $keys The keys in the MapperLocator object.
     *
     */
    public function __construct(MapperLocator $mappers, array $keys = [])
    {
        $this->mappers = $mappers;
        $this->keys = $keys;
    }

    /**
     *
     * Returns the value at the current iterator position.
     *
     * @return AbstractMapper
     *
     */
    public function current()
    {
        return $this->mappers->get($this->key());
    }

    /**
     *
     * Returns the current iterator position.
     *
     * @return string
     *
     */
    public function key()
    {
        return current($this->keys);
    }

    /**
     *
     * Moves the iterator to the next position.
     *
     * @return null
     *
     */
    public function next()
    {
        $this->valid = (next($this->keys) !== false);
    }

    /**
     *
     * Moves the iterator to the first position.
     *
     * @return null
     *
     */
    public function rewind()
    {
        $this->valid = (reset($this->keys) !== false);
    }

    /**
     *
     * Is the current iterator position valid?
     *
     * @return null
     *
     */
    public function valid()
    {
        return $this->valid;
    }
}
