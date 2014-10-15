<?php
namespace Aura\SqlMapper_Bundle;

class FakeMapper extends AbstractMapper
{
    public function getTable()
    {
        return 'aura_test_table';
    }

    public function getPrimaryCol()
    {
        return 'id';
    }

    public function getIdentityField()
    {
        return 'identity';
    }

    public function getColsFields()
    {
        return [
            'id'                    => 'identity',
            'name'                  => 'firstName',
            'test_size_scale'       => 'sizeScale',
            'test_default_null'     => 'defaultNull',
            'test_default_string'   => 'defaultString',
            'test_default_number'   => 'defaultNumber',
            'test_default_ignore'   => 'defaultIgnore',
        ];
    }
}
