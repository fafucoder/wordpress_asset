<?php
namespace Dawn\WordpressAsset;

class PackageManager {
    /**
     * The array of registered elements.
     *
     * @var array
     */
    public $registered = array();

    /**
     * Singleton instance.
     * 
     * @var object
     */
    public static $instance;

    /**
     * Singleton function.
     * 
     * @return object 
     */
    public static function getInstance() {
        self::$instance || self::$instance = new self();

        return self::$instance;
    }

    /**
     * Register package.
     * 
     * @param  mixed $package package name
     * @param  array  $config  
     * @return void          
     */
    public function register($package, $config = array()) {
        if (is_array($package)) {
            foreach ($package as $name => $conf) {
                $conf = array_merge($config, $conf);
                $this->register($name, $conf);
            }
        } else {
            if (class_exists($package)) {
                $package = new $package();
            }
            if ($package instanceof Package) {
                $this->registered[$package->getName()] = $package;
            } else {
                $this->registered[$package] = $config;
            }
        }
    }

    /**
     * Remove a registered package
     *
     * @param string $package
     *
     * @return bool
     */
    public function unregister($package) {
        if (is_array($package)) {
            foreach ($package as $p) {
                $this->unregister($p);
            }
        }
        if ($this->get($package)) {
            unset($this->registered[$package]);
        }
    }

    /**
     * Add package.
     * 
     * @param mixed $package 
     */
    public function add($package, $config = array()) {
        if (is_array($package)) {
            foreach ($package as $p => $conf) {
                $conf = array_merge($config, $conf);
                $this->add($p, $conf);
            }
        } else {
            if (!$this->get($package)) {
                $this->register($package, $config);
            } else {
                $config = $this->get($package);
            }

            return Package::add($package, $config);
        }
    }


    /**
     * Add package.
     * 
     * @param mixed $package 
     */
    public function queue($package, $config = array()) {
        if (is_array($package)) {
            foreach ($package as $p => $conf) {
                $conf = array_merge($config, $conf);
                $this->queue($p);
            }
        } else {
            if (!$this->get($package)) {
                $this->register($package, $config);
            } else {
                $config = $this->get($package);
            }

            return Package::queue($package, $config);
        }
    }

    /**
     * Get a registered pakcage type.if param $type is null, return all registered package
     *
     * @param null|mixed $package
     *
     * @return mixed
     */
    public function get($package = null) {
        if (null === $package) {
            return $this->registered;
        }

        return isset($this->registered[$package]) ? $this->registered[$package] : null;
    }

    /**
     * Check if the package exists.
     *
     * @param string $package
     *
     * @return bool
     */
    public function has($package) {
        return isset($this->registered[$package]);
    }

    /**
     * Return size of registered package
     *
     * @return number
     */
    public function count() {
        return count($this->registered);
    }
}
