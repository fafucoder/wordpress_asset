<?php
namespace Dawn\WordpressAsset;

class Asset extends Configurable {
    /**
     * Asset handle.
     * 
     * @var stirng
     */
    public $name;

    /**
     * Asset path.
     * 
     * @var string
     */
    public $path;

   /**
     * The asset based path.
     * 
     * @var string
     */
    public $base = '';

    /**
     * Asset Dependency.
     *
     * @var array
     */
    public $dependency = array();

    /**
     * Asset version.
     *
     * @var string
     */
    public $version = '1.0.0';

    /**
     * Inline code in the position for the inline asset, This is affect script and not influence css class.
     * 
     * @var string
     */
    public $position = 'after';

    /**
     * The default area where to load assets.
     * 
     * @var string
     */
    public $area = 'front';

    /**
     * The inline data for output.
     * 
     * @var mixed
     */
    public $inline;

    /**
     * The list of registered assets.
     * 
     * @var array
     */
    public static $registered = array();

    /**
     * The list of enqueued assets.
     * 
     * @var array
     */
    public static $enqueued = array();

    /**
     * The asset actions.
     * 
     * @var array
     */
    public static $actions = array(
        'front' => 'wp_enqueue_scripts',
        'admin' => 'admin_enqueue_scripts',
        'login' => 'login_enqueue_scripts',
        'customizer' => 'customize_controls_enqueue_scripts',
        'block' => 'enqueue_block_assets',
        'block_editor' => 'enqueue_block_editor_assets',
    );

    /**
     * Enqueue a asset.
     * 
     * @param  string $name   
     * @param  array  $config
     * 
     * @return object return $this
     */
    public static function queue($name, $config = array()) {
        $asset = new static($name, $config);
        $asset->doEnqueue();

        return $asset;
    }

    /**
     * Register a asset.
     * 
     * @param  string $name   
     * @param  array  $config 
     * 
     * @return object return $this         
     */
    public static function add($name, $config = array()) {
        $asset = new static($name, $config);
        $asset->doRegister();

        return $asset;
    }

    /**
     * Load system asset.
     * 
     * @param  string $name 
     * @return mixed
     */
    public static function load($name) {
        $asset = new static($name);
        $asset->doEnqueue($name);

        return $asset;
    }

    /**
     * Remove asset.
     *
     * @param string $name asset name
     *
     * @return bool
     */
    public static function remove($name) {
        if (static::has($name)) {
            $asset = static::get($name);
            $asset->dequeue()->deregister();

            return true;
        }

        return false;
    }

    /**
     * Get asset object.
     * 
     * @param  string $name  asest name
     * @param  string $type  asset type
     * @return object|null   return $this
     */
    public static function get($name) {
        if (static::has($name)) {
            return static::$registered[$name];
        }
    }

    /**
     * Has a asset.
     * 
     * @param  string  $name asset handle name
     * @param  string  $type asset type
     * 
     * @return boolean       
     */
    public static function has($name) {
        return isset(static::$registered[$name]);
    }

    /**
     * Construct.
     *
     * @param array $config
     * @param mixed $name
     */
    public function __construct($name, $config = array()) {
        if (empty($name)) {
            throw new \LogicException(__(sprintf('The name of Asset "%s" is missing.', self::class), 'creation-framework'));
        }
        $this->name = $name;
        parent::__construct($config);

        if ($this->base) {
            $this->base($this->base);
        }

        static::$registered[$this->name] = $this;
    }

    /**
     * Register asset.
     *
     * @param  string Register name
     * @param mixed $name
     *
     * @return object return $this
     */
    public function doRegister($name = '') {
        if (!$name) {
            foreach (static::$registered as $name => $Asset) {
                if (array_key_exists($Asset->area, static::$actions)) {
                    \add_action(static::$actions[$Asset->area], function () use ($Asset) {
                        $Asset->register();
                    });
                }
            }

            return $this;
        }

        $Asset = static::$registered[$name];
        if ($Asset) {
            if (array_key_exists($Asset->area, static::$actions)) {
                \add_action(static::$actions[$Asset->area], function () use ($Asset) {
                    $Asset->register();
                });
            }
        }

        return $this;
    }

    /**
     * Enqueue assets.
     *
     * @param string enqueue name
     * @param mixed $name
     *
     * @return object return $this
     */
    public function doEnqueue($name = '') {
        if (!$name) {
            foreach (static::$registered as $name => $Asset) {
                if (in_array($name, static::$enqueued) || !isset(static::$actions[$Asset->area])) {
                    continue;
                }

                \add_action(static::$actions[$Asset->area], function () use ($Asset) {
                    $Asset->enqueue();
                });
                static::$enqueued[$name] = $Asset;
            }

            return $this;
        }

        $Asset = static::$registered[$name];
        if ($Asset) {
            if (!array_key_exists($Asset->area, static::$actions)) {
                throw new \InvalidArgumentException(__(sprintf('area not found for area: %s', $Asset->area), 'creation-framework'));
            }
            \add_action(static::$actions[$Asset->area], function () use ($Asset) {
                $Asset->enqueue();
            });

            static::$enqueued[$name] = $Asset;
        } else {
           \add_action(static::$actions[$this->area], function() {
                $this->enqueue();
            });
        }

        return $this;
    }

    /**
     * Add inline code accompany loaded assets.
     * 
     * @param  string $data     the inline data to output
     * @param  string $position the position for inline
     * @return object return $this
     */
    public function inline($data, $position = '') {
        if ($position) {
            $this->in($position);
        }
        if (is_callable($data)) {
            $data = call_user_func($data);
        }

        $this->inline = $data;

        return $this;
    }

    /**
     * Asset depencies.
     * @param  array  $deps 
     * @return object       return $this
     */
    public function dependences($deps = array()) {
        if (is_string($deps)) {
            $dependency = explode(',', $deps);
        }
        $this->dependency = $deps;

        return $this;
    }

    /**
     * Inline code position for the loaded assets, by default in the after loaded asset, have 'before' or 'after' position.
     * 
     * @param  string $position position
     * @return mixed
     */
    public function in($position = 'after') {
        $this->position = $position;

        return $this;
    }

    /**
     * Specify where to load the asset: 'admin', 'login' or 'customizer'.
     *
     * @param string $area specify erea
     *
     * @return object return $this object
     */
    public function area($area = 'front') {
        if (array_key_exists($area, static::$actions)) {
            $this->area = $area;

            //resolve dependency
            foreach ($this->dependency as $dep) {
                if (isset(static::$registered[$dep])) {
                    static::$registered[$dep]->area($area);
                }
            }

            static::$registered[$this->name] = $this;
        }

        return $this;
    }

    /**
     * Set the base asset path.
     * 
     * @param  string $path base path
     * @return object  return $this
     */
    public function base($base = null) {
        if ($base) {
            if ((false === filter_var($base, FILTER_VALIDATE_URL)) && !preg_match('/^\:(?:\\\\|\/\/)/', $base)) {
                $this->base = '/' . $base;
            } else {
                $this->base = $base;
            }
        }

        return $this;
    }

    /**
     * Get the asset base path if not exists base path return this directory.
     * 
     * @return string 
     */
    public function getBase() {
        return $this->base;
    }

    /**
     * Asset path.
     * 
     * @param  string $path 
     * @return object       return $this
     */
    public function path($path) {
        $this->path = $path;

        return $this;
    }

    /**
     * Get asset path.
     * 
     * @return string 
     */
    public function getPath() {
        if (strpos($this->path, 'http://') !== false || strpos($this->path, 'https://') !== false || substr($this->path, 0, 2) === '//') {
            return $this->path;
        }

        //is absolute path.
        if (strpos($this->path, '/') === 0) {
            return $this->path;
        }

        if ($base = $this->getBase()) {
            return rtrim($base, '/') . DIRECTORY_SEPARATOR . $this->path;
        }
        
        return $this->path;
    }

    /**
     * Check asset status.
     *
     * @param string $status asset status
     * @param string $name
     */
    public function is($status = 'enqueued', $name = '') {
    }

    /**
     * resolve style dependency not found.
     *
     * @param array $dependency
     *
     * @return array
     */
    protected function checkDepency($dependency = array()) {
        foreach ($dependency as $key => $dep) {
            if (!$this->is('registered', $dep)) {
                unset($dependency[$key]);
            }
        }

        return $dependency;
    }

    /**
     * Enqueue dependency.
     * 
     * @return array 
     */
    protected function enqueueDependency() {
        $dependency = [];
        foreach ($this->dependency as $dep) {
            if (isset(static::$registered[$dep])) {
                static::$registered[$dep]->enqueue();

                $dependency[] = $dep;
            }
        }

        return $dependency;
    }

    /**
     * Register dependency.
     * 
     * @return array 
     */
    protected function registerDependency() {
        $dependency = [];
        foreach ($this->dependency as $dep) {
            if (isset(static::$registered[$dep])) {
                static::$registered[$dep]->register();

                $dependency[] = $dep;
            }
        }

        return $dependency;
    }
}