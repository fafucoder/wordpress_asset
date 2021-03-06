<?php
namespace Dawn\WordpressAsset;

class Script extends Asset {
    /**
     * The script if defined in the footer.
     *
     * @var bool
     */
    public $footer = false;

    /**
     * The localize data.
     *
     * @var string
     */
    public $localize = array();

    /**
     * Async script.
     *
     * @var bool
     */
    public $async;

    /**
     * Defer script.
     *
     * @var bool
     */
    public $defer;

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
     * The position to load script and have footer or head placed.
     *
     * @param string $position load script position
     *
     * @return mixed
     */
    public function footer() {
        $this->footer = true;

        return $this;
    }

    /**
     * Localize data for the asset.
     *
     * @param string $obj_name
     * @param mixed  $data       any data to attach to the JS variable: string, boolean, object, array, ...
     * @param mixed  $objectName
     *
     * @return object return $this
     */
    public function localize($objectName, $data) {
        if (is_callable($data)) {
            $data = call_user_func($data);
        }
        $this->localize[$objectName] = $data;

        return $this;
    }

    /**
     * async asset.
     *
     * @return object return $this
     */
    public function async() {
        $this->async = true;

        return $this;
    }

    /**
     * Defer script.
     *
     * @return object return $this
     */
    public function defer() {
        $this->defer = true;

        return $this;
    }

    /**
     * Register script.
     *
     * @return object return $this
     */
    public function register() {
        if (!$this->is('registered')) {
            // $dependency = $this->checkDepency($this->dependency);
            $dependency = $this->registerDependency();
            wp_register_script($this->name, $this->getPath(), $dependency, $this->version, $this->footer);
        }

        return $this;
    }

    /**
     * Enqueue script.
     *
     * @return object return $this
     */
    public function enqueue() {
        if (!$this->is('enqueued')) {
            if ($this->is('registered')) {
                wp_enqueue_script($this->name);
            } else {
                // $dependency = $this->checkDepency($this->dependency);
                $dependency = $this->enqueueDependency();

                wp_enqueue_script($this->name, $this->getPath(), $dependency, $this->version, $this->footer);
            }
        }

        $this->inlineAsset();
        $this->localizeAsset();
        add_filter('script_loader_tag', array($this, '_loadTag'), 10, 2);

        return $this;
    }

    /**
     * Load tag.
     *
     * @param string $tag
     * @param string $handle
     *
     * @return string
     */
    public function _loadTag($tag, $handle) {
        if ($this->name === $handle) {
            if ($this->defer) {
                return str_replace(' src', ' defer="defer" src', $tag);
            }

            if ($this->async) {
                return str_replace(' src', ' async="async" src', $tag);
            }
        }

        return $tag;
    }

    /**
     * Unregister script.
     *
     * @return object return $this
     */
    public function deregister() {
        if ($this->is('registered')) {
            wp_deregister_script($this->name);
            unset(static::$registered[$this->name]);
        }

        return $this;
    }

    /**
     * Dequeue script.
     *
     * @return object return $this
     */
    public function dequeue() {
        if ($this->is('enqueued')) {
            wp_dequeue_script($this->name);
            unset(static::$enqueued[$this->name]);
        }

        return $this;
    }

    /**
     * Localize script.
     *
     * @return object return $this
     */
    public function localizeAsset() {
        if (isset($this->localize) && !empty($this->localize)) {
            foreach ($this->localize as $obj => $data) {
                wp_localize_script($this->name, $obj, $data);
            }
        }

        return $this;
    }

    /**
     * Inline script.
     *
     * @return object return $this
     */
    public function inlineAsset() {
        if (isset($this->inline) && !empty($this->inline)) {
            wp_add_inline_script($this->name, $this->inline, $this->position);
        }

        return $this;
    }

    /**
     * Check asset status.
     *
     * @param  string asset name
     * @param string $status asset status
     * @param mixed  $name
     *
     * @return bool
     */
    public function is($status = 'enqueued', $name = '') {
        if (empty($name)) {
            $name = $this->name;
        }

        if (empty($name)) {
            return false;
        }

        return wp_script_is($name, $status);
    }
}