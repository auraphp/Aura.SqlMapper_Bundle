<?php
namespace Aura\SqlMapper_Bundle;

class FakeMapper extends AbstractMapper
{
    public $cols_fields = [
        'id'                    => 'id',
        'name'                  => 'firstName',
        'test_size_scale'       => 'sizeScale',
        'test_default_null'     => 'defaultNull',
        'test_default_string'   => 'defaultString',
        'test_default_number'   => 'defaultNumber',
        'test_default_ignore'   => 'defaultIgnore',
    ];

    public function getTable()
    {
        return 'aura_test_table';
    }

    public function getPrimaryCol()
    {
        return 'id';
    }

    public function getColsFields()
    {
        return ($this->cols_fields)
             ? $this->cols_fields
             : parent::getColsFields();
    }
}
