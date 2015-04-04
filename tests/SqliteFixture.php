<?php
namespace Aura\SqlMapper_Bundle;

class SqliteFixture
{
    protected $create_table = "CREATE TABLE aura_test_table (
        id       INTEGER PRIMARY KEY AUTOINCREMENT,
        name     VARCHAR(50) NOT NULL UNIQUE,
        building INTEGER,
        floor    INTEGER
    )";

    public $connection;

    public $table;

    public function __construct($connection, $table)
    {
        $this->connection = $connection;
        $this->table = $table;
    }

    public function exec()
    {
        $this->createTable();
        $this->fillTable();
    }

    protected function createTable()
    {
        $sql = $this->create_table;
        $this->connection->query($sql);
    }

    protected function fillTable()
    {
        $data = [
            ['Anna',  1, 1],
            ['Betty', 1, 2],
            ['Clara', 1, 3],
            ['Donna', 1, 1],
            ['Edna',  1, 2],
            ['Fiona', 1, 3],
            ['Gina',  2, 1],
            ['Hanna', 2, 2],
            ['Ione',  2, 3],
            ['Julia', 2, 1],
            ['Kara',  2, 2],
            ['Lana',  2, 3],
        ];

        $stm = "INSERT INTO {$this->table} (name, building, floor)
                VALUES (:name, :building, :floor)";

        foreach ($data as $vals) {
            list($name, $building, $floor) = $vals;
            $this->connection->perform($stm, [
                'name' => $name,
                'building' => $building,
                'floor' => $floor
            ]);
        }
    }
}
