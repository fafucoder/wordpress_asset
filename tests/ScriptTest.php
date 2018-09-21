<?php
namespace FaFu\Asset\Tests;

use FaFu\Asset\Asset;
use FaFu\Asset\Style;
use MonkeryTestCase\BrainMonkeyWpTestCase as WP_UnitTestCase;

class ScriptTest extends WP_UnitTestCase {
    public $old_wp_scripts;

    public function setUp() {
        parent::setUp();

        $this->old_wp_scripts = isset($GLOBALS['wp_scripts']) ? $GLOBALS['wp_scripts'] : null;
        remove_action( 'wp_default_scripts', 'wp_default_scripts' );
        $GLOBALS['wp_scripts'] = new \WP_Scripts();
        $GLOBALS['wp_scripts']->default_version = get_bloginfo('version');
        $GLOBALS['wp_scripts']->base_url = 'http://example.org';
    }

    function tearDown() {
        Script::$registered = array();
        Script::$enqueued = array();

        $GLOBALS['wp_scripts'] = $this->old_wp_scripts;
        add_action( 'wp_default_scripts', 'wp_default_scripts' );

        parent::tearDown();
    }

    public function testConstruct() {
        $script = new Script('foo', array());

        $this->assertSame('foo', $script->name);
        $this->assertSame('1.0.0', $script->ver);
        $this->assertSame(array(), $script->deps);
        $this->assertSame('front', $script->area);
        $this->assertSame('after', $script->position);
        $this->assertSame(false, $script->footer);
        $this->assertSame(array(), $script->localize);
    }

    public function testFooter() {
        $script = new Script('foo', array());
        $this->assertSame(false, $script->footer);

        $script->footer();
        $this->assertSame(true, $script->footer);
    }

    public function testLocalize() {
        $script = new Script('foo');
        $this->assertEquals(array(), $script->localize);

        $script->localize('variable', array('data' => 'value'));
        $this->assertEquals(array('variable' => array('data' => 'value')), $script->localize);

        $script->localize('variable', function(){
            return array('data' => 'data');
        });
        $this->assertEquals(array('variable' => array('data' => 'data')), $script->localize);
    }

    public function testDefer() {
        $script = new Script('foo');
        $this->assertNull($script->defer);

        $script->defer();
        $this->assertTrue($script->defer);
    }

    public function testAsync() {
        $script = new Script('foo');
        $this->assertNull($script->async);

        $script->async();
        $this->assertTrue($script->async);
    }

    public function testRegister() {
        $script = new Script('foo', array(
            'path' => '/fixtures/foo.js'
        ));

        $this->assertFalse(wp_script_is('foo', 'registered'));

        $script->register();
        $this->assertTrue(wp_script_is('foo', 'registered'));
    }

    public function testEnqueue() {
        $script = new Script('bar', array(
            'path' => '/fixtures/bar.js',
        ));

        $script->enqueue();

        $expected = "<script type='text/javascript' src='http://example.org/fixtures/bar.js?ver=1.0.0'></script>\n";
        $this->assertEquals($expected, get_echo('wp_print_scripts'));
    }

    public function testEnqueueAssetWithFooter() {
        $script = new Script('bar', array(
            'path' => '/fixtures/bar.js',
        ));
        $script->footer();
        $script->enqueue();

        $this->assertEquals('', get_echo('wp_print_head_scripts'));
        $expected = "<script type='text/javascript' src='http://example.org/fixtures/bar.js?ver=1.0.0'></script>\n";
        $this->assertEquals($expected, get_echo('wp_print_footer_scripts'));
    }

    public function testLocalizeAsset() {
        $script = new Script('bar', array(
            'path' => '/fixtures/bar.js',
        ));
        $script->localize('variable', array('foo' => 'bar'));
        $script->enqueue();

        $expected = "<script type='text/javascript'>\n/* <![CDATA[ */\nvar variable = {\"foo\":\"bar\"};\n/* ]]> */\n</script>\n";
        $expected .= "<script type='text/javascript' src='http://example.org/fixtures/bar.js?ver=1.0.0'></script>\n";
        
        $this->assertEquals($expected, get_echo('wp_print_scripts'));
    }

    public function testInlineAsset() {
        $script = new Script('bar', array(
            'path' => '/fixtures/bar.js',
        ));
        $script->inline('console.log("hello world");');
        $script->enqueue();

        $expected = "<script type='text/javascript' src='http://example.org/fixtures/bar.js?ver=1.0.0'></script>\n";
        $expected .= "<script type='text/javascript'>\nconsole.log(\"hello world\");\n</script>\n";

        $this->assertEquals($expected, get_echo('wp_print_scripts'));
    }

    public function testInlineAssetWithPosition() {
        $script = new Script('bar', array(
            'path' => '/fixtures/bar.js',
        ));
        $script->in('before');
        $script->inline('console.log("hello world");');
        $script->enqueue();

        $expected = "<script type='text/javascript'>\nconsole.log(\"hello world\");\n</script>\n";
        $expected .= "<script type='text/javascript' src='http://example.org/fixtures/bar.js?ver=1.0.0'></script>\n";
        
        $this->assertEquals($expected, get_echo('wp_print_scripts'));
    }

    public function testLoadTagDefer() {
        $script = new Script('bar', array(
            'path' => '/fixtures/bar.js',
        ));
        $script->footer();
        $script->defer();
        $script->enqueue();

        $expected = "<script type='text/javascript' defer=\"defer\" src='http://example.org/fixtures/bar.js?ver=1.0.0'></script>\n";
        $this->assertEquals($expected, get_echo('wp_print_footer_scripts'));
    }

    public function testLoadTagAsync() {
        $script = new Script('foo', array(
            'path' => '/fixtures/foo.js',
        ));
        $script->footer();
        $script->async();
        $script->enqueue();

        $expected = "<script type='text/javascript' async=\"async\" src='http://example.org/fixtures/foo.js?ver=1.0.0'></script>\n";
        $this->assertEquals($expected, get_echo('wp_print_footer_scripts'));
    }

    public function testIs() {
        $script = new Script('foo', array(
            'path' => '/fixtures/foo.js',
        ));

        $script->register();
        $this->assertTrue($script->is('registered'));
        $this->assertFalse($script->is('enqueued'));

        $script->enqueue();
        $this->assertTrue($script->is('enqueued'));
    }

    public function testDoRegister() {
        $script = new Script('bar', array(
            'path' => '/fixtures/bar.js',
        ));

        $this->assertFalse($script->is('registered'));
        $script->doRegister();
        do_action('wp_enqueue_scripts');

        $this->assertTrue($script->is('registered'));
    }

    public function testDoEnqueue() {
        $script = new Script('foo', array(
            'path' => '/fixture/foo.js'
        ));

        $this->assertFalse($script->is('enqueued'));
        $script->doEnqueue();
        do_action('wp_enqueue_scripts');

        $this->assertTrue($script->is('enqueued'));
    }

    public function testAdd() {
        $this->assertFalse(wp_script_is('bar', 'registered'));
        $this->assertFalse(wp_script_is('bar', 'enqueued'));

        Script::add('bar', array(
            'path' => '/fixters/bar.js',
        ));

        do_action('wp_enqueue_scripts');

        $this->assertTrue(wp_script_is('bar', 'registered'));
        $this->assertFalse(wp_script_is('bar', 'enqueued'));
    }

    public function testQueue() {
        $this->assertFalse(wp_script_is('foo', 'registered'));
        $this->assertFalse(wp_script_is('foo', 'enqueued'));

        Script::queue('foo', array(
            'path' => '/fixtures/foo.js',
        ));

        do_action('wp_enqueue_scripts');
        $this->assertTrue(wp_script_is('foo', 'registered'));
        $this->assertTrue(wp_script_is('foo', 'enqueued'));

        $expected = "<script type='text/javascript' src='http://example.org/fixtures/foo.js?ver=1.0.0'></script>\n";
        $this->assertEquals($expected, get_echo('wp_print_scripts'));
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
    }

    public function testRemove() {
        $this->assertFalse(wp_script_is('foo', 'registered'));
        $this->assertFalse(wp_script_is('foo', 'enqueued'));

        Script::queue('foo', array(
            'path' => '/fixtures/foo.js',
        ));

        do_action('wp_enqueue_scripts');
        $this->assertTrue(wp_script_is('foo', 'registered'));
        $this->assertTrue(wp_script_is('foo', 'enqueued'));

        Script::remove('foo');
        $this->assertFalse(wp_script_is('foo', 'registered'));
        $this->assertFalse(wp_script_is('foo', 'enqueued'));
    }

    public function testHas() {
        Script::add('foo', array(
            'path' => '/fixtures/foo.js',
        ));
        
        $this->assertTrue(Script::has('foo'));
        $this->assertFalse(Script::has('bar'));
    }
}