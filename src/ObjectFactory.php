<?php
namespace Aura\SqlMapper_Bundle;

use ArrayObject;

class ObjectFactory implements ObjectFactoryInterface
{
    public function newObject(array $row = array())
    {
        return (object) $row;
    }

    public function newCollection(array $objects = array())
    {
        return new ArrayObject($objects);
    }

    public function newObjectAssortment(
        array $objects = array(),
        $missing = null
    ) {
        return new Assortment($objects, $missing);
    }

    public function newCollectionAssortment(
        array $collections = array(),
        $missing = null
    ) {
        return new Assortment($collections, $missing);
    }
}
