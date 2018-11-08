<?php
namespace FaFu\Asset\Tests;

use FaFu\Asset\PackageManager;
use FaFu\Asset\Package;
use MonkeryTestCase\BrainMonkeyWpTestCase as WP_UnitTestCase;

class PackageManagerTest extends WP_UnitTestCase {
    public function setUp() {
        $this->manager = new PackageManager();
    }

    public function testGet() {
        $this->manager->register(array(
            'jquery' => array('scripts' => array('jquery.js')),
            'bootstrap' => array('scripts' => array('bootstrap.js')),
            'react' => array('scripts' => array('react.js')),
            'vue' => array('scripts' => array('vue.js')),
        ));

        $this->assertEquals(array('scripts' => array('jquery.js')), $this->manager->get('jquery'));
        $this->assertEquals(null, $this->manager->get('foo'));
    }

    public function testHas() {
        $this->manager->register(array(
            'jquery' => array('scripts' => array('jquery.js')),
            'bootstrap' => array('scripts' => array('bootstrap.js')),
            'react' => array('scripts' => array('react.js')),
            'vue' => array('scripts' => array('vue.js')),
        ));

        $this->assertTrue($this->manager->has('jquery'));
        $this->assertTrue($this->manager->has('react'));
        $this->assertTrue($this->manager->has('bootstrap'));
    }

    public function testCount() {
        $this->manager->register(array(
            'jquery' => array('scripts' => array('jquery.js')),
            'bootstrap' => array('scripts' => array('bootstrap.js')),
            'react' => array('scripts' => array('react.js')),
            'vue' => array('scripts' => array('vue.js')),
        ));

        $this->assertEquals(4, $this->manager->count());
    }

    public function testRegister() {
        $manager = new PackageManager();
        $this->assertEmpty($this->manager->registered);

        $this->manager->register('jquery', array(
            'scripts' => array(
                '/fixrutes/jquery.js',
            ),
            'styles' => array(
                '/fixtures/jquery.css',
            ),
            'dependency' => array(),
        ));

        $this->assertArrayHasKey('jquery', $this->manager->registered);
        $this->assertEquals(array(
            'scripts' => array(
                '/fixrutes/jquery.js',
            ),
            'styles' => array(
                '/fixtures/jquery.css',
            ),
            'dependency' => array(),
        ), $this->manager->get('jquery'));
    }

    public function testRegisterAsArray() {
        $manager = new PackageManager();
        $this->assertEmpty($this->manager->registered);

        $this->manager->register(array(
            'jquery' => array('scripts' => array('jquery.js')),
            'bootstrap' => array('scripts' => array('bootstrap.js')),
            'react' => array('scripts' => array('react.js')),
            'vue' => array('scripts' => array('vue.js')),
        ), array(
            'base' => '/fixture',
            'area' => 'admin',
        ));

        $this->assertArrayHasKey('jquery', $this->manager->registered);
        $this->assertArrayHasKey('react', $this->manager->registered);
        $this->assertArrayHasKey('vue', $this->manager->registered);

        $expected = array(
            'scripts' => array('jquery.js'),
            'base' => '/fixture',
            'area' => 'admin',
        );
        $this->assertEquals($expected, $this->manager->get('jquery'));
    }

    public function testUnregister() {
        $manager = new PackageManager();
        $this->assertEmpty($this->manager->registered);
    }

    public function testAdd() {
        $manager = new PackageManager();
        $this->assertEmpty($this->manager->registered);

        $this->manager->add('react', array(
            'scripts' => array(
                'react' => array(
                    'path' => 'react.js',
                ),
            ),
            'styles' => array(
                'react' => array(
                    'path' => 'react.css',
                ),
            ),
            'base' => '/fixfure',
            'footer' => true,
        ));

        $this->assertArrayHasKey('react', $this->manager->registered);
        $this->assertTrue(Package::has('react'));
    }

    public function testQueue() {
        $manager = new PackageManager();
        $this->assertEmpty($this->manager->registered);

        $this->manager->add('reactdom', array(
            'scripts' => array(
                'reactdom' => array(
                    'path' => 'reactdom.js',
                ),
            ),
            'styles' => array(
                'reactdom' => array(
                    'path' => 'reactdom.css',
                ),
            ),
            'base' => '/fixfure',
            'footer' => true,
        ));

        $this->assertArrayHasKey('reactdom', $this->manager->registered);
        $this->assertTrue(Package::has('reactdom'));
    }
}