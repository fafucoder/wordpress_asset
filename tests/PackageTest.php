<?php
namespace Dawn\WordpressAsset\Tests;

use Dawn\WordpressAsset\Script;
use Dawn\WordpressAsset\Style;
use Dawn\WordpressAsset\Package;
use MonkeryTestCase\BrainMonkeyWpTestCase as WP_UnitTestCase;

class PackageTest extends WP_UnitTestCase {
    public $old_wp_scripts;
    public $old_wp_styles;

    public function setUp() {
        parent::setUp();

        $this->old_wp_scripts = isset($GLOBALS['wp_scripts']) ? $GLOBALS['wp_scripts'] : null;
        remove_action( 'wp_default_scripts', 'wp_default_scripts' );
        $GLOBALS['wp_scripts'] = new \WP_Scripts();
        $GLOBALS['wp_scripts']->default_version = get_bloginfo('version');
        $GLOBALS['wp_scripts']->base_url = 'http://example.org';

        $this->old_wp_styles = isset($GLOBALS['wp_styles']) ? $GLOBALS['wp_styles'] : null;
        remove_action('wp_default_styles', '_wp_default_styles');
        remove_action( 'wp_print_styles', 'print_emoji_styles' );
        $GLOBALS['wp_styles'] = new \WP_Styles();
        $GLOBALS['wp_styles']->default_version = get_bloginfo('version');
        $GLOBALS['wp_styles']->base_url = 'http://example.org';
    }

    public function tearDown() {
        Package::$registered = array();
        Package::$enqueued = array();

        Style::$registered = array();
        Style::$enqueued = array();

        $GLOBALS['wp_styles'] = $this->old_wp_styles;
        add_action( 'wp_default_styles', 'wp_default_styles' );
        add_action( 'wp_print_styles', 'print_emoji_styles' );

        Script::$registered = array();
        Script::$enqueued = array();

        $GLOBALS['wp_scripts'] = $this->old_wp_scripts;
        add_action( 'wp_default_scripts', 'wp_default_scripts' );

        parent::tearDown();
    }

    public function testStyles() {
        $package = new Package('foo', array(
            'styles' => array(
                'foo' => array(
                    'path' => '/fixtures/foo.css',
                ),
                'bar' => array(
                    'path' => '/fixtures/bar.css',
                ),
            ),
            'version' => '1.2.5',
            'base' => '/assets/test',
            'erea' => 'login',
        ));

        $expected = array(
            'foo' => array(
                'path' => '/fixtures/foo.css',
                'version' => '1.2.5',
                'base' => '/assets/test',
                'erea' => 'login',
            ),
            'bar' => array(
                'path' => '/fixtures/bar.css',
                'version' => '1.2.5',
                'base' => '/assets/test',
                'erea' => 'login',
            ),
        );

        $this->assertEquals($expected, $package->getStyles());
    }

    public function testScripts() {
        $package = new Package('foo', array(
            'scripts' => array(
                'foo' => array(
                    'path' => '/fixtures/foo.js',
                ),
                'bar' => array(
                    'path' => '/fixtures/bar.js',
                ),
            ),
            'version' => '1.5.5',
            'base' => '/assets/test',
            'erea' => 'admin',
        ));

        $expected = array(
            'foo' => array(
                'path' => '/fixtures/foo.js',
                'version' => '1.5.5',
                'base' => '/assets/test',
                'erea' => 'admin',
            ),
            'bar' => array(
                'path' => '/fixtures/bar.js',
                'version' => '1.5.5',
                'base' => '/assets/test',
                'erea' => 'admin',
            ),
        );

        $this->assertEquals($expected, $package->getScripts());
    }

    public function testRegister() {
        $package = Package::add('foo', array(
            'scripts' => array(
                'foo' => array(
                    'path' => '/fixtures/foo.js',
                ),
                'bar' => array(
                    'path' => '/fixtures/bar.js',
                ),
            ),
            'styles' => array(
                'foo' => array(
                    'path' => '/fixtures/foo.css',
                ),
                'bar' => array(
                    'path' => '/fixtures/bar.css',
                ),
            ),
            'version' => '1.2.5',
            'base' => '/assets/test',
        ));

        $this->assertTrue(Script::has('foo'));
        $this->assertTrue(Script::has('bar'));
        $this->assertTrue(Style::has('foo'));
        $this->assertTrue(Style::has('bar'));

        do_action('wp_enqueue_scripts');

        $foo = Script::get('foo');
        $this->assertTrue($foo->is('registered'));

        $foo = Style::get('foo');
        $this->assertTrue($foo->is('registered'));
    }

    public function testQueue() {
        $package = Package::queue('bar', array(
            'scripts' => array(
                'choice' => array(
                    'path' => '/fixtures/choice.js',
                ),
                'map' => array(
                    'path' => '/fixtures/map.js',
                ),
            ),
            'styles' => array(
                'choice' => array(
                    'path' => '/fixtures/choice.css',
                ),
                'map' => array(
                    'path' => '/fixtures/map.css',
                ),
            ),
            'ver' => '1.2.5',
            'base' => '/assets/test',
        ));

        $this->assertTrue(Script::has('choice'));
        $this->assertTrue(Script::has('map'));
        $this->assertTrue(Style::has('choice'));
        $this->assertTrue(Style::has('map'));

        do_action('wp_enqueue_scripts');

        $choice = Style::get('choice');
        $this->assertTrue($choice->is('enqueued'));

        $style = "<link rel='stylesheet' id='choice-css'  href='http://example.org/fixtures/choice.css?ver=1.2.5' type='text/css' media='screen' />\n";
        $style .= "<link rel='stylesheet' id='map-css'  href='http://example.org/fixtures/map.css?ver=1.2.5' type='text/css' media='screen' />\n";
        $this->assertEquals($style, get_echo('wp_print_styles'));

        $choice = Script::get('choice');
        $this->assertTrue($choice->is('enqueued'));
        
        $scripts = "<script type='text/javascript' src='http://example.org/fixtures/choice.js?ver=1.2.5'></script>\n";
        $scripts .= "<script type='text/javascript' src='http://example.org/fixtures/map.js?ver=1.2.5'></script>\n";

        $this->assertEquals($scripts, get_echo('wp_print_scripts'));
    }

    public function testRemove() {
        $package = Package::queue('foo', array(
            'scripts' => array(
                'foo' => array(
                    'path' => '/fixtures/foo.js',
                ),
                'bar' => array(
                    'path' => '/fixtures/bar.js',
                ),
            ),
            'styles' => array(
                'foo' => array(
                    'path' => '/fixtures/foo.css',
                ),
                'bar' => array(
                    'path' => '/fixtures/bar.css',
                ),
            ),
            'version' => '1.2.5',
            'base' => '/assets/test',
        ));

        $this->assertTrue(Script::has('foo'));
        $this->assertTrue(Script::has('bar'));
        $this->assertTrue(Style::has('foo'));
        $this->assertTrue(Style::has('bar'));

        do_action('wp_enqueue_scripts');
        $foo = Script::get('foo');
        $this->assertTrue($foo->is('enqueued'));

        $foo = Style::get('foo');
        $this->assertTrue($foo->is('enqueued'));

        Package::remove('foo');

        $this->assertFalse(Script::has('foo'));
        $this->assertFalse(Script::has('bar'));
        $this->assertFalse(Style::has('foo'));
        $this->assertFalse(Style::has('bar'));
    }

    public function testHas() {
        Package::add('foo', array());
        $this->assertTrue(Package::has('foo'));

        Package::add('bar', array());
        $this->assertTrue(Package::has('bar'));
        $this->assertFalse(Package::has('foo_bar'));
    }

    public function testGet() {
        Package::add('foo', array(
            'scripts' => array(
                'foo' => array(
                    'path' => '/fixtures/foo.js',
                ),
            ),
            'styles' => array(
                'foo' => array(
                    'path' => '/fixtures/foo.css',
                ),
            ),
            'ver' => '1.2.5',
        ));

        $this->assertTrue(Package::has('foo'));
        do_action('wp_enqueue_scripts');

        $this->assertFalse(wp_script_is('foo', 'enqueued'));
        $this->assertTrue(wp_script_is('foo', 'registered'));

        $this->assertFalse(wp_style_is('foo', 'enqueued'));
        $this->assertTrue(wp_style_is('foo', 'registered'));

        $package = Package::get('foo');
        $this->assertInstanceOf(Package::class, $package);

        $package->enqueue();
        do_action('wp_enqueue_scripts');
        $this->assertTrue(wp_script_is('foo', 'enqueued'));
        $this->assertTrue(wp_style_is('foo', 'enqueued'));
    }
}