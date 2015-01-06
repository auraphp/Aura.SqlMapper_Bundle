<?php
namespace Aura\SqlMapper_Bundle;

class ObjectFactory implements ObjectFactoryInterface
{
    public function newObject(array $row = array())
    {
        return (object) $row;
    }

    public function newCollection(array $rows = array())
    {
        $coll = array();
        foreach ($rows as $row) {
            $coll[] = $this->newObject($row);
        }
        return $coll;
    }
}
