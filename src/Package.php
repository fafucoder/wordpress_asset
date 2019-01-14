<?php
namespace Dawn\WordpressAsset;

class Package  extends Configurable {
    /**
     * The package name.
     *
     * @var string
     */
    protected $name;

    /**
     * The scripts.
     *
     * @var array
     */
    protected $scripts = array();

    /**
     * The Styles.
     *
     * @var array
     */
    protected $styles = array();

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
     * @param string $name   package name
     * @param array  $config package config
     *
     * @return mixed
     */
    public static function add($name, $config = array()) {
        if (is_array($name)) {
            foreach ($name as $n => $conf) {
                static::add($n, $conf);
            }
        } else {
            $package = new self($name, $config);
            $package->register();

            return $package;
        }
    }

    /**
     * Enqueue a package.
     *
     * @param string $name   package name
     * @param array  $config package config
     *
     * @return object return this instance
     */
    public static function queue($name, $config = array()) {
        if (is_array($name)) {
            foreach ($name as $n => $conf) {
                static::queue($n, $conf);
            }
        } else {
            $package = new self($name, $config);
            $package->enqueue();

            return $package;
        }
    }

    /**
     * Remove package.
     *
     * @param string $name package name
     *
     * @return object return this instance
     */
    public static function remove($name) {
        $package = new self($name);
        $package->deregister();
    }

    /**
     * Has package.
     *
     * @param string $name package name
     *
     * @return bool
     */
    public static function has($name) {
        return isset(static::$registered[$name]) || isset(static::$enqueued[$name]);
    }

    /**
     * Get package.
     *
     * @param string $name package name
     *
     * @return object
     */
    public static function get($name) {
        if (static::has($name)) {
            return isset(static::$registered[$name]) ? static::$registered[$name] : static::$enqueued[$name];
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

        $keys = array_keys(get_object_vars($this));
        foreach ($keys as $key) {
            if (isset($config[$key])) {
                $this->$key = $config[$key];
            }
        }
    }

    /**
     * Register package.
     *
     * @return $this
     */
    public function register() {
        if (!isset(static::$registered[$this->name])) {
            foreach ($this->getStyles() as $name => $style) {
                $name = $this->getAssetName($name);
                Style::add($name, $style);
            }

            foreach ($this->getScripts() as $name => $script) {
                $name = $this->getAssetName($name);
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
        if (isset(static::$enqueued[$this->name])) {
            return $this;
        }
        if (array_key_exists($this->name, static::$registered)) {
            $asset = static::$registered[$this->name];
            foreach ($asset->getStyles() as $name => $style) {
                $name = $this->getAssetName($name);
                Style::queue($name, $style);
            }
            foreach ($asset->getScripts() as $name => $script) {
                $name = $this->getAssetName($name);
                Script::queue($name, $script);
            }

            static::$enqueued[$this->name] = $this;

            return $this;
        }

        foreach ($this->getStyles() as $name => $style) {
            $name = $this->getAssetName($name);
            Style::queue($name, $style);
        }
        foreach ($this->getScripts() as $name => $script) {
            $name = $this->getAssetName($name);
            Script::queue($name, $script);
        }

        static::$enqueued[$this->name] = $this;

        return $this;
    }

    /**
     * Deregister package.
     *
     * @return object return this instance
     */
    public function deregister() {
        if (isset(static::$enqueued[$this->name]) || isset(static::$registered[$this->name])) {
            $package = isset(static::$enqueued[$this->name]) ? static::$enqueued[$this->name] : static::$registered[$this->name];
            foreach ($package->getStyles() as $name => $style) {
                Style::remove($name);
            }

            foreach ($package->getScripts() as $name => $script) {
                Script::remove($name);
            }

            unset(static::$enqueued[$this->name], static::$registered[$this->name]);
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
     * @param array $config
     *
     * @return array sliced and filed array
     */
    protected function config($config = array()) {
        $diff = array_diff_key($config, array_flip(array('styles', 'scripts')));

        // may be this need further optimization.
        return array_map(function ($value) use ($diff) {
            if (is_array($value)) {
                foreach ($value as $key => $v) {
                    if (!is_array($v)) {
                        $v = array(
                            'path' => $v,
                        );
                    }
                    $value[$key] = array_merge($diff, $v);
                }
            }

            return $value;
        }, $config);
    }

    /**
     * Get asset name.
     *
     * @param string|int $name
     *
     * @return string
     */
    protected function getAssetName($name) {
        if (is_numeric($name)) {
            return $this->name;
        }

        return $name;
    }
}