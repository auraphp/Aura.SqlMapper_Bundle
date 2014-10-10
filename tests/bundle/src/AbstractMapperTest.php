<?php
namespace Aura\SqlMapper_Bundle;

use Aura\SqlQuery\QueryFactory;

class AbstractMapperTest extends \PHPUnit_Framework_TestCase
{
    use Assertions;

    protected $mapper;

    protected function setUp()
    {
        parent::setUp();
        $this->mapper = new FakeMapper;
        $this->query = new QueryFactory('sqlite');
    }

    public function testGetCols()
    {
        $expect = [
            'id',
            'name',
            'test_size_scale',
            'test_default_null',
            'test_default_string',
            'test_default_number',
            'test_default_ignore',
        ];
        $actual = $this->mapper->getCols();
        $this->assertSame($expect, $actual);
    }

    public function testGetColForField()
    {
        $expect = 'id';
        $actual = $this->mapper->getColForField('identity');
        $this->assertSame($expect, $actual);
    }

    public function testGetFields()
    {
        $expect = [
            'identity',
            'firstName',
            'sizeScale',
            'defaultNull',
            'defaultString',
            'defaultNumber',
            'defaultIgnore',
        ];
        $actual = $this->mapper->getFields();
        $this->assertSame($expect, $actual);
    }

    public function testGetFieldForCol()
    {
        $expect = 'identity';
        $actual = $this->mapper->getFieldForCol('id');
        $this->assertSame($expect, $actual);
    }

    public function testGetIdentityField()
    {
        $expect = 'identity';
        $actual = $this->mapper->getIdentityField('id');
        $this->assertSame($expect, $actual);
    }

    public function testGetIdentityValue()
    {
        $object = (object) [
            'identity' => 88
        ];

        $expect = 88;
        $actual = $this->mapper->getIdentityValue($object);
        $this->assertSame($expect, $actual);

    }

    public function testGetPrimaryCol()
    {
        $expect = 'id';
        $actual = $this->mapper->getPrimaryCol('id');
        $this->assertSame($expect, $actual);
    }

    public function testGetTable()
    {
        $expect = 'aura_test_table';
        $actual = $this->mapper->getTable();
        $this->assertSame($expect, $actual);
    }

    public function testGetTableCol()
    {
        $expect = 'aura_test_table.name';
        $actual = $this->mapper->getTableCol('name');
        $this->assertSame($expect, $actual);
    }

    public function testGetTableColAsField()
    {
        $expect = 'aura_test_table.name AS firstName';
        $actual = $this->mapper->getTableColAsField('name');
        $this->assertSame($expect, $actual);
    }

    public function testGetTablePrimaryCol()
    {
        $expect = 'aura_test_table.id';
        $actual = $this->mapper->getTablePrimaryCol();
        $this->assertSame($expect, $actual);
    }

    public function testGetTableColsAsFields()
    {
        $expect = [
            'aura_test_table.id AS identity',
            'aura_test_table.name AS firstName',
            'aura_test_table.test_size_scale AS sizeScale',
            'aura_test_table.test_default_null AS defaultNull',
            'aura_test_table.test_default_string AS defaultString',
            'aura_test_table.test_default_number AS defaultNumber',
            'aura_test_table.test_default_ignore AS defaultIgnore',
        ];

        $actual = $this->mapper->getTableColsAsFields([
            'id',
            'name',
            'test_size_scale',
            'test_default_null',
            'test_default_string',
            'test_default_number',
            'test_default_ignore',
        ]);

        $this->assertSame($expect, $actual);
    }

    public function testModifySelect()
    {
        $select = $this->query->newSelect();
        $this->mapper->modifySelect($select);
        $actual = $select->__toString();
        $expect = '
            SELECT
                "aura_test_table"."id" AS "identity",
                "aura_test_table"."name" AS "firstName",
                "aura_test_table"."test_size_scale" AS "sizeScale",
                "aura_test_table"."test_default_null" AS "defaultNull",
                "aura_test_table"."test_default_string" AS "defaultString",
                "aura_test_table"."test_default_number" AS "defaultNumber",
                "aura_test_table"."test_default_ignore" AS "defaultIgnore"
            FROM
                "aura_test_table"
        ';

        $this->assertSameSql($expect, $actual);
    }

    public function testModifyInsert()
    {
        $object = (object) [
            'identity' => null,
            'firstName' => 'Laura',
            'sizeScale' => 10,
            'defaultNull' => null,
            'defaultString' => null,
            'defaultNumber' => null,
            'defaultIgnore' => null,
        ];

        $insert = $this->query->newInsert();
        $this->mapper->modifyInsert($insert, $object);

        $actual = $insert->__toString();
        $expect = '
            INSERT INTO "aura_test_table" (
                "id",
                "name",
                "test_size_scale",
                "test_default_null",
                "test_default_string",
                "test_default_number",
                "test_default_ignore"
            ) VALUES (
                :id,
                :name,
                :test_size_scale,
                :test_default_null,
                :test_default_string,
                :test_default_number,
                :test_default_ignore
            )
        ';
        $this->assertSameSql($expect, $actual);

        $actual = $insert->getBindValues();
        $expect = [
            'id' => null,
            'name' => 'Laura',
            'test_size_scale' => 10,
            'test_default_null' => null,
            'test_default_string' => null,
            'test_default_number' => null,
            'test_default_ignore' => null,
        ];
        $this->assertSame($expect, $actual);
    }

    public function testModifyUpdate()
    {
        $object = (object) [
            'identity' => 88,
            'firstName' => 'Laura',
            'sizeScale' => 10,
            'defaultNull' => null,
            'defaultString' => null,
            'defaultNumber' => null,
            'defaultIgnore' => null,
        ];

        $update = $this->query->newUpdate();
        $this->mapper->modifyUpdate($update, $object);

        $actual = $update->__toString();
        $expect = '
            UPDATE "aura_test_table"
            SET
                "id" = :id,
                "name" = :name,
                "test_size_scale" = :test_size_scale,
                "test_default_null" = :test_default_null,
                "test_default_string" = :test_default_string,
                "test_default_number" = :test_default_number,
                "test_default_ignore" = :test_default_ignore
            WHERE
                id = ?
        ';
        $this->assertSameSql($expect, $actual);

        $actual = $update->getBindValues();
        $expect = [
            'id' => 88,
            'name' => 'Laura',
            'test_size_scale' => 10,
            'test_default_null' => null,
            'test_default_string' => null,
            'test_default_number' => null,
            'test_default_ignore' => null,
            1 => 88,
        ];
        $this->assertSame($expect, $actual);
    }

    public function testModifyUpdateChanges()
    {
        $object = (object) [
            'identity' => 88,
            'firstName' => 'Laura',
            'sizeScale' => 10,
            'defaultNull' => null,
            'defaultString' => null,
            'defaultNumber' => null,
            'defaultIgnore' => null,
        ];

        $initial_data = [
            'identity' => 88,
            'firstName' => 'Lora',
            'sizeScale' => 10,
            'defaultNull' => null,
            'defaultString' => null,
            'defaultNumber' => null,
            'defaultIgnore' => null,
        ];

        $update = $this->query->newUpdate();
        $this->mapper->modifyUpdate($update, $object, $initial_data);

        $actual = $update->__toString();
        $expect = '
            UPDATE "aura_test_table"
            SET
                "name" = :name
            WHERE
                id = ?
        ';
        $this->assertSameSql($expect, $actual);

        $actual = $update->getBindValues();
        $expect = [
            'name' => 'Laura',
            1 => 88,
        ];
        $this->assertSame($expect, $actual);
    }

    public function testModifyDelete()
    {
        $object = (object) [
            'identity' => 88,
            'firstName' => 'Laura',
            'sizeScale' => 10,
            'defaultNull' => null,
            'defaultString' => null,
            'defaultNumber' => null,
            'defaultIgnore' => null,
        ];

        $delete = $this->query->newDelete();
        $this->mapper->modifyDelete($delete, $object);

        $actual = $delete->__toString();
        $expect = '
            DELETE FROM "aura_test_table"
            WHERE
                id = ?
        ';
        $this->assertSameSql($expect, $actual);

        $actual = $delete->getBindValues();
        $expect = [1 => 88];
        $this->assertSame($expect, $actual);
    }

    public function testGetInsertData()
    {
        $object = (object) [
            'identity' => null,
            'firstName' => 'Laura',
            'sizeScale' => 10,
            'defaultNull' => null,
            'defaultString' => null,
            'defaultNumber' => null,
            'defaultIgnore' => null,
        ];

        $expect = [
            'id' => null,
            'name' => 'Laura',
            'test_size_scale' => 10,
            'test_default_null' => null,
            'test_default_string' => null,
            'test_default_number' => null,
            'test_default_ignore' => null,
        ];

        $actual = $this->mapper->getInsertData($object);
        $this->assertSame($expect, $actual);
    }

    public function testGetUpdateData()
    {
        $object = (object) [
            'identity' => 88,
            'firstName' => 'Laura',
            'sizeScale' => 10,
            'defaultNull' => null,
            'defaultString' => null,
            'defaultNumber' => null,
            'defaultIgnore' => null,
        ];

        $expect = [
            'id' => 88,
            'name' => 'Laura',
            'test_size_scale' => 10,
            'test_default_null' => null,
            'test_default_string' => null,
            'test_default_number' => null,
            'test_default_ignore' => null,
        ];

        $actual = $this->mapper->getUpdateData($object);
        $this->assertSame($expect, $actual);
    }

    public function testGetUpdateDataChanges()
    {
        $object = (object) [
            'identity' => 88,
            'firstName' => 'Laura',
            'sizeScale' => 10,
            'defaultNull' => null,
            'defaultString' => null,
            'defaultNumber' => null,
            'defaultIgnore' => null,
        ];

        $initial_data = [
            'identity' => 88,
            'firstName' => 'Lora',
            'sizeScale' => 10,
            'defaultNull' => null,
            'defaultString' => null,
            'defaultNumber' => null,
            'defaultIgnore' => null,
        ];

        $expect = [
            'name' => 'Laura',
        ];

        // uses getUpdateDataChanges()
        $actual = $this->mapper->getUpdateData($object, $initial_data);
        $this->assertSame($expect, $actual);
    }

    public function testCompare()
    {
        $new_numeric = 88;
        $old_numeric = "69";
        $compare = $this->mapper->compare($new_numeric, $old_numeric);
        $this->assertFalse($compare);

        $new_numeric = 88;
        $old_numeric = "88";
        $compare = $this->mapper->compare($new_numeric, $old_numeric);
        $this->assertTrue($compare);

        $new_string = "Foo";
        $old_string = "Bar";
        $compare = $this->mapper->compare($new_string, $old_string);
        $this->assertFalse($compare);

        $new_string = "Foo";
        $old_string = "Foo";
        $compare = $this->mapper->compare($new_string, $old_string);
        $this->assertTrue($compare);
    }
}
