<?php
namespace Aura\SqlMapper_Bundle;

class FakeEntity
{
    public $id;
    public $firstName;
    public $sizeScale;
    public $defaultNull;
    public $defaultString;
    public $defaultNumber;
    public $defaultIgnore;

    public function __construct($object = null)
    {
        foreach ((array) $object as $field => $value) {
            $this->$field = $value;
        }
    }
}
