<?php
namespace Aura\SqlMapper_Bundle;

interface FilterInterface
{
    public function forInsert($subject);
    public function forUpdate($subject);
}
