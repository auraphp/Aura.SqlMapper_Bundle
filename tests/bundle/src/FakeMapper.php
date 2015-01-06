<?php
namespace Aura\SqlMapper_Bundle;

class FakeMapper extends AbstractMapper
{
    public function getColsFields()
    {
        return [
            'id'                    => 'id',
            'name'                  => 'firstName',
            'test_size_scale'       => 'sizeScale',
            'test_default_null'     => 'defaultNull',
            'test_default_string'   => 'defaultString',
            'test_default_number'   => 'defaultNumber',
            'test_default_ignore'   => 'defaultIgnore',
        ];
    }
}
