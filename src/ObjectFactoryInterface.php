<?php
namespace Aura\SqlMapper_Bundle;

interface ObjectFactoryInterface
{
    public function newObject(array $row = array());
    public function newCollection(array $rows = array());
}
