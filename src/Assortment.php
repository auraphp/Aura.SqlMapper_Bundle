<?php
namespace Aura\SqlMapper_Bundle;

use ArrayObject;

class Assortment extends ArrayObject
{
    protected $missing;

    public function __construct(array $members, $missing)
    {
        parent::__construct($members);
        $this->missing = $missing;
    }

    public function pick($key)
    {
        if ($this->offsetExists($key)) {
            return $this->offsetGet($key);
        }

        if (is_callable($this->missing)) {
            return call_user_func($this->missing);
        }

        return $this->missing;
    }
}
