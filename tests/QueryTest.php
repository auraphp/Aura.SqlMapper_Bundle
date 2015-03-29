<?php
namespace Aura\SqlMapper_Bundle;

use Aura\Sql\ExtendedPdo;
use Aura\Sql\ConnectionLocator;
use Aura\SqlMapper_Bundle\Query\ConnectedQueryFactory;
use Aura\SqlQuery\QueryFactory as QueryFactory;
use StdClass;

class QueryTest extends \PHPUnit_Framework_TestCase
{
    use Assertions;

    protected $query;
    protected $connections;

    public function __construct()
    {
        $this->connection = new ExtendedPdo('sqlite::memory:');

        $this->query = new ConnectedQueryFactory(new QueryFactory('sqlite'));

        $fixture = new SqliteFixture(
            $this->connection,
            'aura_test_table'
        );
        $fixture->exec();
    }

    public function testInsertAndFetchId()
    {
        $insert = $this->query->newInsert($this->connection);
        $insert->into('aura_test_table')
            ->cols([
                'id' => null,
                'name' => 'Mona',
                'building' => 10,
            ]);

        $affected = $insert->perform();
        $this->assertTrue($affected == 1);

        $expect = 13;
        $this->assertEquals($expect, $insert->fetchId('id'));
    }

    public function testUpdate()
    {
        $update = $this->query->newUpdate($this->connection);
        $update->table('aura_test_table')
            ->cols(['name' => 'Annabelle'])
            ->where('id = :id')
            ->bindValue('id', 1);

        $affected = $update->perform();
        $this->assertTrue($affected == 1);
    }

    public function testDelete()
    {
        $delete = $this->query->newDelete($this->connection)
            ->from('aura_test_table')
            ->where('name = ?', 'Anna');

        $affected = $delete->perform();
        $this->assertTrue($affected == 1);
    }

    public function testSelect()
    {
        $select = $this->query->newSelect($this->connection)
            ->cols(['id', 'name'])
            ->from('aura_test_table')
            ->where('name = ?', 'Anna');

        $expect = [
            ['id' => '1', 'name' => 'Anna']
        ];
        $this->assertEquals($expect, $select->fetchAll());

        $expect = [
            '1' => ['id' => '1', 'name' => 'Anna']
        ];
        $this->assertEquals($expect, $select->fetchAssoc());

        $expect = ['1'];
        $this->assertEquals($expect, $select->fetchCol());

        $expect = (object) ['id' => '1', 'name' => 'Anna'];
        $this->assertEquals($expect, $select->fetchObject());

        $expect = [
            (object) ['id' => '1', 'name' => 'Anna']
        ];
        $this->assertEquals($expect, $select->fetchObjects());

        $expect = ['id' => '1', 'name' => 'Anna'];
        $this->assertEquals($expect, $select->fetchOne());

        $expect = ['1' => 'Anna'];
        $this->assertEquals($expect, $select->fetchPairs());

        $expect = '1';
        $this->assertEquals($expect, $select->fetchValue());
    }
}
