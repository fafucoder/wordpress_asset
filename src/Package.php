<?php
namespace FaFu\Asset;

use FaFu\Asset\Configurable;
use FaFu\Asset\Script;
use FaFu\Asset\Style;

class Package extends Configurable {
    /**
     * The package name.
     * 
     * @var string
     */
    public $name;

    /**
     * The scripts.
     * 
     * @var array
     */
    public $scripts = array();

    /**
     * The Styles.
     * 
     * @var array
     */
    public $styles = array();

    /**
     * Registered package.
     * 
     * @var array
     */
    public static $registered = array();

    /**
     * Enqueued package.
     * 
     * @var array
     */
    public static $enqueued = array();

    /**
     * Register a new package.
     * 
     * @param  string $name   package name
     * @param  array  $config package config
     * @return mixed         
     */
    public static function add($name, $config = array()) {
        $package = new Package($name, $config);
        $package->register();

        return $package;
    }

    /**
     * Enqueue a package.
     * 
     * @param  string $name   package name
     * @param  array  $config package config
     * @return object         return this instance
     */
    public static function queue($name, $config = array()) {
        $package = new Package($name, $config);
        $package->enqueue();

        return $package;
    }

    /**
     * Remove package.
     *
     * @param  string $name package name
     * @return object return this instance       
     */
    public static function remove($name) {
        $package = new Package($name);
        $package->deregister();

    }

    /**
     * Has package.
     * 
     * @param  string  $name package name
     * @return boolean       
     */
    public static function has($name) {
        return isset(static::$registered[$name]);
    }

    /**
     * Get package.
     * 
     * @param  string $name package name
     * @return object
     */
    public static function get($name) {
        if (static::has($name)) {
            return static::$registered[$name];
        }
    }

    /**
     * Construct.
     * 
     * @param string $name   package name
     * @param array  $config package config
     */
    public function __construct($name, $config = array()) {
        $this->name = $name;
        $config = $this->config($config);

        parent::__construct($config);
    }

    /**
     * Register package.
     * 
     * @return  objcet return this instance
     */
    public function register() {
        if (!isset(static::$registered[$this->name])) {
            foreach ($this->styles as $name => $style) {
                Style::add($name, $style);
            }

            foreach ($this->scripts as $name => $script) {
                Script::add($name, $script);
            }

            static::$registered[$this->name] = $this;
        }

        return $this;
    }

    /**
     * Enqueue package.
     * 
     * @return object return this instance
     */
    public function enqueue() {
        if (!isset(static::$enqueued[$this->name])) {
            foreach ($this->styles as $name => $style) {
                Style::queue($name, $style);
            }

            foreach ($this->scripts as $name => $script) {
                Script::queue($name, $script);
            }

            static::$enqueued[$this->name] = $this;
        }

        return $this;
    }

    /**
     * Deregister package.
     * 
     * @return object return this instance
     */
    public function deregister() {
        if (isset(static::$enqueued[$this->name]) || isset(static::$registered[$this->name])) {
            $package = static::$enqueued[$this->name];
            foreach ($package->getStyles() as $name => $style) {
                Style::remove($name);
            }

            foreach ($package->getScripts() as $name => $script) {
                Script::remove($name);
            }

            unset(static::$enqueued[$this->name]);
            unset(static::$registered[$this->name]);
        }

        return $this;
    }

    /**
     * Get package style assets.
     * 
     * @return array 
     */
    public function getStyles() {
        return $this->styles;
    }

    /**
     * Get package script assets.
     * 
     * @return array
     */
    public function getScripts() {
        return $this->scripts;
    }

    /**
     * Get package name.
     * 
     * @return string 
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Config slice and fill.
     * 
     * @param  array  $config 
     * @return array         sliced and filed array
     */
    protected function config($config = array()) {
        $diff = array_diff_key($config, array_flip(array('styles', 'scripts')));

        // may be this need further optimization.
        return array_map(function($value) use ($diff) {
            if (is_array($value)) {
                foreach ($value as $key => $v) {
                    if (!is_array($v)) {
                        continue;
                    }
                    $value[$key] = array_merge($v, $diff);
                }
            }
            return $value;
        }, $config);
    }
}