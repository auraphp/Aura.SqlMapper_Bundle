<?php
namespace Aura\SqlMapper_Bundle;

class MapperLocatorTest extends \PHPUnit_Framework_TestCase
{
    protected $mappers;

    protected function setUp()
    {
        parent::setUp();
        $registry = [
            'posts' => function() {
                $mapper = (object) ['type' => 'post'];
                return $mapper;
            },
            'comments' => function() {
                $mapper = (object) ['type' => 'comment'];
                return $mapper;
            },
            'authors' => function() {
                $mapper = (object) ['type' => 'author'];
                return $mapper;
            },
        ];

        $this->mappers = new MapperLocator($registry);
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function testSetAndGet()
    {
        $this->mappers->set('tags', function () {
            $mapper = (object) ['type' => 'tag'];
            return $mapper;
        });

        $mapper = $this->mappers->get('tags');
        $this->assertTrue($mapper->type == 'tag');
    }

    public function testGet_noSuchGateway()
    {
        $this->setExpectedException('Aura\SqlMapper_Bundle\Exception\NoSuchMapper');
        $mapper = $this->mappers->get('no-such-mapper');
    }

    public function test_iterator()
    {
        $expect = ['post', 'comment', 'author'];
        foreach ($this->mappers as $mapper) {
            $actual[] = $mapper->type;
        }
        $this->assertSame($expect, $actual);
    }
}
