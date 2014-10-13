<?php
namespace Aura\SqlMapper_Bundle;

class SqliteFixture
{
    protected $create_table = "CREATE TABLE aura_test_table (
         id                     INTEGER PRIMARY KEY AUTOINCREMENT
        ,name                   VARCHAR(50) NOT NULL
        ,test_size_scale        NUMERIC(7,3)
        ,test_default_null      CHAR(3) DEFAULT NULL
        ,test_default_string    VARCHAR(7) DEFAULT 'string'
        ,test_default_number    NUMERIC(5) DEFAULT 12345
        ,test_default_ignore    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    public $connection;

    public $table;

    public function __construct($connection, $table, $schema1, $schema2)
    {
        $this->connection = $connection;
        $this->table = $table;
        $this->schema1 = $schema1;
        $this->schema2 = $schema2;
    }

    public function exec()
    {
        $this->dropSchemas();
        $this->createSchemas();
        $this->createTables();
        $this->fillTable();
    }

    protected function createSchemas()
    {
        // only need to create the second one
        $this->connection->query("ATTACH DATABASE ':memory:' AS aura_test_schema2");
    }

    protected function dropSchemas()
    {
        // all in memory, no need to clean up
    }

    protected function createTables()
    {
        // create in schema 1
        $sql = $this->create_table;
        $this->connection->query($sql);

        // create again in schema 2
        $sql = str_replace($this->table, "{$this->schema2}.{$this->table}", $sql);
        $this->connection->query($sql);
    }

    // only fills in schema 1
    protected function fillTable()
    {
        $names = [
            'Anna', 'Betty', 'Clara', 'Donna', 'Fiona',
            'Gertrude', 'Hanna', 'Ione', 'Julia', 'Kara',
        ];

        $stm = "INSERT INTO {$this->table} (name) VALUES (:name)";
        foreach ($names as $name) {
            $this->connection->perform($stm, ['name' => $name]);
        }
    }
}
