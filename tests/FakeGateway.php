<?php
namespace Aura\SqlMapper_Bundle;

class FakeGateway extends AbstractGateway
{
    public function getTable()
    {
        return 'aura_test_table';
    }

    public function getPrimaryCol()
    {
        return 'id';
    }
}
