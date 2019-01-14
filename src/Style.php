<?php
namespace Dawn\WordpressAsset;

class Style extends Asset {
    /**
     * The media.
     *
     * @var string
     */
    public $media = 'screen';

    /**
     * The style attributes.
     *
     * @var string
     */
    public $attribute = array();

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
     * The media.
     *
     * @param string $media
     *
     * @return object return $this
     */
    public function media($media = 'all') {
        $this->media = $media;

        return $this;
    }

    /**
     * The style attributes.
     *
     * @param string $attribute attributes.
     *
     * @return object return $this
     */
    public function attribute($attribute = array()) {
        if (is_callable($attribute)) {
            $attribute = call_user_func($attribute);
        }
        $this->attribute = $attribute;

        return $this;
    }

    /**
     * Register style.
     *
     * @return object return $this
     */
    public function register() {
        if (!$this->is('registered')) {
            // $dependency = $this->checkDepency($this->dependency);
            $dependency = $this->registerDependency();
            wp_register_style($this->name, $this->getPath(), $dependency, $this->version, $this->media);
        }

        return $this;
    }

    /**
     * Unregister asset.
     *
     * @return object return $this
     */
    public function deregister() {
        if ($this->is('registered')) {
            wp_deregister_style($this->name);
            unset(static::$registered[$this->name]);
        }

        return $this;
    }

    /**
     * Dequeue style.
     *
     * @return object return $this
     */
    public function dequeue() {
        if ($this->is('enqueued')) {
            wp_dequeue_style($this->name);
            unset(static::$enqueued[$this->name]);
        }

        return $this;
    }

    /**
     * Enqueue style.
     *
     * @return object return $this.
     */
    public function enqueue() {
        if (!$this->is('enqueued')) {
            if ($this->is('registered')) {
                wp_enqueue_style($this->name);
            } else {
                // $dependency = $this->checkDepency($this->dependency);
                $dependency = $this->enqueueDependency();
                wp_enqueue_style($this->name, $this->getPath(), $dependency, $this->version, $this->media);
            }
        }
        $this->inlineAsset();
        add_filter('style_loader_tag', array($this, '_loadTag'), 10, 4);

        return $this;
    }

    /**
     * Load tag.
     *
     * @param string $html   style html
     * @param string $handle handle name
     * @param string $href   style href
     * @param string $media
     *
     * @return string
     */
    public function _loadTag($html, $handle, $href, $media) {
        if ($this->name === $handle) {
            foreach ($this->attribute as $key => $value) {
                if (false !== strpos($html, $key)) {
                    //may be need optimizer
                    $pattens = "/(?<=$key=[\'|\"]).+?(?=[\'|\"])/i";
                    $html = preg_replace($pattens, $value, $html);
                } else {
                    $replace = "$key=$value />";
                    $html = str_replace('/>', $replace, $html);
                }
            }

            return $html;
        }

        return $html;
    }

    /**
     * Inline style.
     *
     * @return object return $this
     */
    public function inlineAsset() {
        if (isset($this->inline) && !empty($this->inline)) {
            wp_add_inline_style($this->name, $this->inline);
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

        return wp_style_is($name, $status);
    }
}