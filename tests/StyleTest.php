<?php
namespace FaFu\Asset\Tests;

use FaFu\Asset\Asset;
use FaFu\Asset\Style;

class StyleTest extends \WP_UnitTestCase {
    public $old_wp_styles;

    public function setUp() {
        parent::setUp();

        $this->old_wp_styles = isset($GLOBALS['wp_styles']) ? $GLOBALS['wp_styles'] : null;
        remove_action('wp_default_styles', '_wp_default_styles');
        remove_action( 'wp_print_styles', 'print_emoji_styles' );
        $GLOBALS['wp_styles'] = new \WP_Styles();
        $GLOBALS['wp_styles']->default_version = get_bloginfo('version');
        $GLOBALS['wp_styles']->base_url = 'http://example.org';
    }

    function tearDown() {
        Style::$registered = array();
        Style::$enqueued = array();

        $GLOBALS['wp_styles'] = $this->old_wp_styles;
        add_action( 'wp_default_styles', 'wp_default_styles' );
        add_action( 'wp_print_styles', 'print_emoji_styles' );

        parent::tearDown();
    }

    public function testConstruct() {
        $style = new Style('foo', array());

        $this->assertSame('foo', $style->name);
        $this->assertSame('1.0.0', $style->ver);
        $this->assertSame(array(), $style->deps);
        $this->assertSame('front', $style->area);
        $this->assertSame('after', $style->position);
        $this->assertSame('screen', $style->media);
        $this->assertSame(array(), $style->attribute);
    }

    public function testMedia() {
        $style = new Style('foo', array(
            'media' => 'screen',
        ));

        $this->assertSame('screen', $style->media);
        $style->media('print');
        $this->assertSame('print', $style->media);
    }

    public function testAttribute() {
        $style = new Style('foo', array());
        $this->assertEquals(array(), $style->attribute);

        $style->attribute(array(
            'sizes' => '16x16',
            'rel' => 'icon',
        ));

        $this->assertEquals(array('sizes' => '16x16', 'rel' => 'icon'), $style->attribute);

        $style->attribute(function() {
            return array('target' => '_blank');
        });
        $this->assertEquals(array('target' => '_blank'), $style->attribute);
    }

    public function testRegister() {
        $style = new Style('foo', array(
            'path' => '/fixtures/foo.css'
        ));

        $this->assertFalse(wp_style_is('foo', 'registered'));

        $style->register();
        $this->assertTrue(wp_style_is('foo', 'registered'));
    }

    public function testEnqueue() {
        $style = new Style('bar', array(
            'path' => '/fixtures/bar.css',
        ));

        $style->enqueue();
        $expected = "<link rel='stylesheet' id='bar-css'  href='http://example.org/fixtures/bar.css?ver=1.0.0' type='text/css' media='screen' />";
        $this->assertContains($expected, get_echo('wp_print_styles'));
    }

    public function testEnqueueWithMedia() {
        $style = new Style('bar', array(
            'path' => '/fixtures/bar.css',
        ));
        $style->media('print and (min-width: 25cm)');
        $style->enqueue();

        $expected = "<link rel='stylesheet' id='bar-css'  href='http://example.org/fixtures/bar.css?ver=1.0.0' type='text/css' media='print and (min-width: 25cm)' />";
        $this->assertContains($expected, get_echo('wp_print_styles'));
    }

    public function testEnqueueWithAttribute() {
        $style = new Style('foo', array(
            'path' => '/fixtures/foo.css',
        ));

        $style->attribute(array(
            'sizes' => "16x16",
            'rel' => 'icon',
            'media' => 'print',
        ));
        $style->enqueue();

        $expected = "<link rel='icon' id='foo-css'  href='http://example.org/fixtures/foo.css?ver=1.0.0' type='text/css' media='print' sizes=16x16 />\n";
        $this->assertEquals($expected, get_echo('wp_print_styles'));
    }

    public function testInlineAsset() {
        $style = new Style('foo', array(
            'path' => '/fixtures/foo.css',
        ));

        $inline = "a {color: blue; }";
        $style->inline($inline);
        $style->enqueue();

        $expected = "<link rel='stylesheet' id='foo-css'  href='http://example.org/fixtures/foo.css?ver=1.0.0' type='text/css' media='screen' />\n";
        $expected .= "<style id='foo-inline-css' type='text/css'>\n";
        $expected .= "$inline\n";
        $expected .= "</style>\n";

        $this->assertEquals($expected, get_echo('wp_print_styles'));
    }

    public function testIs() {
        $style = new Style('foo', array(
            'path' => '/fixtures/foo.css',
        ));

        $style->register();
        $this->assertTrue($style->is('registered'));

        $style->enqueue();
        $this->assertTrue($style->is('enqueued'));
    }

    public function testDoRegister() {
        $style = new Style('bar', array(
            'path' => '/fixtures/bar.css',
        ));

        $this->assertFalse($style->is('registered'));

        $style->doRegister();
        do_action('wp_enqueue_scripts');

        $this->assertTrue($style->is('registered'));
    }

    public function testDoEnqueue() {
        $style = new Style('foo', array(
            'path' => '/fixture/foo.css'
        ));

        $this->assertFalse($style->is('enqueued'));

        $style->doEnqueue();
        do_action('wp_enqueue_scripts');

        $this->assertTrue($style->is('enqueued'));
    }

    public function testAdd() {
        $this->assertFalse(wp_style_is('bar', 'registered'));
        $this->assertFalse(wp_style_is('bar', 'enqueued'));

        Style::add('bar', array(
            'path' => '/fixters/bar.css',
        ));

        do_action('wp_enqueue_scripts');

        $this->assertTrue(wp_style_is('bar', 'registered'));
        $this->assertFalse(wp_style_is('bar', 'enqueued'));
    }

    public function testQueue() {
        $this->assertFalse(wp_style_is('foo', 'registered'));
        $this->assertFalse(wp_style_is('foo', 'enqueued'));

        Style::queue('foo', array(
            'path' => '/fixtures/foo.css',
        ));

        do_action('wp_enqueue_scripts');
        $this->assertTrue(wp_style_is('foo', 'registered'));
        $this->assertTrue(wp_style_is('foo', 'enqueued'));
    }

    public function testGet() {
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

    public function testRemove() {
        $this->assertFalse(wp_style_is('foo', 'registered'));
        $this->assertFalse(wp_style_is('foo', 'enqueued'));

        Style::queue('foo', array(
            'path' => '/fixtures/foo.css',
        ));

        do_action('wp_enqueue_scripts');
        $this->assertTrue(wp_style_is('foo', 'registered'));
        $this->assertTrue(wp_style_is('foo', 'enqueued'));

        Style::remove('foo');
        $this->assertFalse(wp_style_is('foo', 'registered'));
        $this->assertFalse(wp_style_is('foo', 'enqueued'));
    }

    public function testHas() {
        Style::add('foo', array(
            'path' => '/fixtures/foo.css',
        ));
        
        $this->assertTrue(Style::has('foo'));
        $this->assertFalse(Style::has('bar'));
    }
}