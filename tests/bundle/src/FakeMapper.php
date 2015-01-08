<?php
namespace Aura\SqlMapper_Bundle;

class FakeMapper extends AbstractMapper
{
    public function getColsFields()
    {
        return [
            'id'      => 'id',
            'name'    => 'firstName',
            'building' => 'buildingNumber',
            'floor' => 'floor',
        ];
    }
}
