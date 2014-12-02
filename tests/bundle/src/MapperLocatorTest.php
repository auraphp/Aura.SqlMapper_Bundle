<?php
namespace Aura\SqlMapper_Bundle;

class MapperLocatorTest extends \PHPUnit_Framework_TestCase
{
    protected $mapper_locator;

    protected function setUp()
    {
        parent::setUp();
        $factories = [
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

        $this->mapper_locator = new MapperLocator($factories);
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function testGet()
    {
        $mapper = $this->mapper_locator->get('posts');
        $this->assertTrue($mapper->type == 'post');
    }

    public function testGet_noSuchMapper()
    {
        $this->setExpectedException('Aura\SqlMapper_Bundle\Exception\NoSuchMapper');
        $mapper = $this->mapper_locator->get('no-such-mapper');
    }

    public function test_iterator()
    {
        $expect = ['post', 'comment', 'author'];
        foreach ($this->mapper_locator as $mapper) {
            $actual[] = $mapper->type;
        }
        $this->assertSame($expect, $actual);
    }
}
