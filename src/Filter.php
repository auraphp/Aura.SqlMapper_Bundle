<?php
namespace Aura\SqlMapper_Bundle;

class Filter implements FilterInterface
{
    public function forInsert($subject)
    {
        return $subject;
    }

    public function forUpdate($subject)
    {
        return $subject;
    }
}
