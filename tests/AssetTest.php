<?php
namespace FaFu\Asset\Tests;

use FaFu\Asset\Asset;
use FaFu\Asset\Style;
use FaFu\Asset\Script;
use MonkeryTestCase\BrainMonkeyWpTestCase as WP_UnitTestCase;

class AssetTest extends WP_UnitTestCase {
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

    function tearDown() {
        Asset::$registered = array();
        Asset::$enqueued = array();

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

    public function testConstruct() {
        $asset = new Asset('foo', array());

        $this->assertSame('foo', $asset->name);
        $this->assertSame('1.0.0', $asset->ver);
        $this->assertSame(array(), $asset->deps);
        $this->assertSame('front', $asset->area);
        $this->assertSame('', $asset->base);
        $this->assertSame('after', $asset->position);
    }

    public function testBase() {
        $asset = new Asset('bar', array(
            'base' => 'asset/asset',
        ));
        $this->assertEquals('asset/asset', $asset->base);

        $asset->base('asset');
        $this->assertEquals('asset', $asset->base);

        $asset->base('asset/assets/');
        $this->assertEquals('/asset/assets/', $asset->base);
    }

    public function testPath() {
        $asset = new Asset('bar', array(
            'path' => 'bar.css',
        ));
        $this->assertSame('bar.css', $asset->path);
        $this->assertEquals('bar.css', $asset->getPath());

        $asset->path('/fixtures/bar.css');
        $this->assertEquals('/fixtures/bar.css', $asset->getPath());

        $asset->path('http://example.org/bar.css');
        $this->assertEquals('http://example.org/bar.css', $asset->getPath());
    }

    public function testArea() {
        $asset = new Asset('foo', array());
        $this->assertEquals('front', $asset->area);

        $asset->area('admin');
        $this->assertEquals('admin', $asset->area);

        $asset->area('widget');
        $this->assertEquals('admin', $asset->area);
    }

    public function testIn() {
        $asset = new Asset('foo');
        $this->assertEquals('after', $asset->position);

        $asset->in('before');
        $this->assertEquals('before', $asset->position);
    }

    public function testDependences() {
        $asset = new Asset('bar');
        $this->assertEquals(array(), $asset->deps);

        $asset->dependences(array('jquery', 'foo'));
        $this->assertEquals(array('jquery', 'foo'), $asset->deps);
    }

    public function testInline() {
        $asset = new Asset('foo', array(
            'position' => 'before',
        ));

        $this->assertNull($asset->inline);
        $this->assertEquals('before', $asset->position);

        $asset->inline('.color {border: 1px solid red; }');
        $this->assertEquals('.color {border: 1px solid red; }', $asset->inline);

        $asset->inline(function() {
            return ".color: {padding: 10px; margin: 20px}";
        });
        $this->assertEquals(".color: {padding: 10px; margin: 20px}", $asset->inline);

        $asset->inline('console.log("hello world")', 'after');
        $this->assertEquals('console.log("hello world")', $asset->inline);
        $this->assertEquals('after', $asset->position);
    }

    public function testAdd() {
        $this->assertFalse(wp_style_is('bar', 'registered'));
        $this->assertFalse(wp_style_is('bar', 'enqueued'));
        $this->assertFalse(wp_script_is('bar', 'registered'));
        $this->assertFalse(wp_script_is('bar', 'enqueued'));

        Style::add('bar', array(
            'path' => '/fixters/bar.css',
        ));
        Script::add('bar', array(
            'path' => '/fixters/bar.js',
        ));
        do_action('wp_enqueue_scripts');

        $this->assertTrue(wp_script_is('bar', 'registered'));
        $this->assertFalse(wp_script_is('bar', 'enqueued'));
        $this->assertTrue(wp_style_is('bar', 'registered'));
        $this->assertFalse(wp_style_is('bar', 'enqueued'));
    }

    public function testQueue() {
        $this->assertFalse(wp_style_is('bar', 'registered'));
        $this->assertFalse(wp_style_is('bar', 'enqueued'));
        $this->assertFalse(wp_script_is('bar', 'registered'));
        $this->assertFalse(wp_script_is('bar', 'enqueued'));

        Style::queue('bar', array(
            'path' => '/fixters/bar.css',
        ));
        Script::queue('bar', array(
            'path' => '/fixters/bar.js',
        ));
        do_action('wp_enqueue_scripts');

        $this->assertTrue(wp_script_is('bar', 'registered'));
        $this->assertTrue(wp_script_is('bar', 'enqueued'));
        $this->assertTrue(wp_style_is('bar', 'registered'));
        $this->assertTrue(wp_style_is('bar', 'enqueued'));

        $style = "<link rel='stylesheet' id='bar-css'  href='http://example.org/fixters/bar.css?ver=1.0.0' type='text/css' media='screen' />\n";
        $this->assertEquals($style, get_echo('wp_print_styles'));

        $script = "<script type='text/javascript' src='http://example.org/fixters/bar.js?ver=1.0.0'></script>\n";
        $this->assertEquals($script, get_echo('wp_print_scripts'));
    }

    public function testRemove() {
        $this->assertFalse(wp_style_is('foo', 'registered'));
        $this->assertFalse(wp_style_is('foo', 'enqueued'));
        $this->assertFalse(wp_script_is('foo', 'registered'));
        $this->assertFalse(wp_script_is('foo', 'enqueued'));

        Style::queue('foo', array(
            'path' => '/fixtures/foo.css',
        ));
        Script::queue('foo', array(
            'path' => '/fixtures/foo.js',
        ));
        do_action('wp_enqueue_scripts');

        $this->assertTrue(wp_style_is('foo', 'registered'));
        $this->assertTrue(wp_style_is('foo', 'enqueued'));
        $this->assertTrue(wp_script_is('foo', 'registered'));
        $this->assertTrue(wp_script_is('foo', 'enqueued'));

        Style::remove('foo');
        Script::remove('foo');

        $this->assertFalse(wp_style_is('foo', 'registered'));
        $this->assertFalse(wp_style_is('foo', 'enqueued'));
        $this->assertFalse(wp_script_is('foo', 'registered'));
        $this->assertFalse(wp_script_is('foo', 'enqueued'));

    }

    public function testGet() {
        Script::add('foo', array(
            'path' => '/fixtures/foo.js',
        ));

        $script = Script::get('foo');
        $this->assertInstanceOf(Asset::class, $script);

        $script
            ->inline('console.log("hello world");')
            ->in('before')
            ->localize('variable', array('foo' => 'bar'))
            ->footer()
            ->path('/fixtures/foo/bar.js')
            ->area('login')
            ->async()
            ->doEnqueue();

        do_action('login_enqueue_scripts');

        $expected  = "<script type='text/javascript'>\n/* <![CDATA[ */\nvar variable = {\"foo\":\"bar\"};\n/* ]]> */\n</script>\n";
        $expected .= "<script type='text/javascript'>\nconsole.log(\"hello world\");\n</script>\n";
        $expected .= "<script type='text/javascript' async=\"async\" src='http://example.org/fixtures/foo/bar.js?ver=1.0.0'></script>\n";
        $this->assertEquals($expected, get_echo('wp_print_footer_scripts'));

        Style::add('bar', array(
            'path' => '/fixters/bar.css',
        ));

        $asset = Style::get('bar');
        $this->assertInstanceOf(Asset::class, $asset);

        $asset
            ->media('orientation: portrait')
            ->inline('div: {border: 10px solid;}')
            ->path('/fixtures/bar/bar.css')
            ->area('login')
            ->doEnqueue();

        do_action('login_enqueue_scripts');

        $expected = "<link rel='stylesheet' id='bar-css'  href='http://example.org/fixtures/bar/bar.css?ver=1.0.0' type='text/css' media='orientation: portrait' />\n";
        $expected .= "<style id='bar-inline-css' type='text/css'>\n";
        $expected .= "div: {border: 10px solid;}\n";
        $expected .= "</style>\n";

        $this->assertEquals($expected, get_echo('wp_print_styles')); 
    }

    public function testHas() {
        Style::add('foo', array(
            'path' => '/fixtures/foo.css',
        ));
        
        $this->assertTrue(Style::has('foo'));
        $this->assertFalse(Style::has('bar'));

        Script::add('foo', array(
            'path' => '/fixtures/foo.js',
        ));
        
        $this->assertTrue(Script::has('foo'));
        $this->assertFalse(Script::has('bar'));
    }
}