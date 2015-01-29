<?php
namespace Aura\SqlMapper_Bundle;

interface ObjectFactoryInterface
{
    public function newObject(array $row = array());
    public function newCollection(array $rows = array());
    public function newObjectAssortment(array $objects = array(), $missing = null);
    public function newCollectionAssortment(array $collections = array(), $missing = null);
}
