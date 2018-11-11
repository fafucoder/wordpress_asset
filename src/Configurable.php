<?php
namespace Dawn\WordpressAsset;

abstract class Configurable {
	/**
	 * Construct.
	 * 
	 * @param array $config 
	 */
	public function __construct($config = array()) {
		static::configurable($this, $config);
	}

	/**
	 * Configuable object property.
	 * 
	 * @param  object $object 
	 * @param  array $config 
	 * @return object
	 */
	public static function configurable($object, $config) {
		foreach ($config as $name => $value) {
			$object->$name = $value;
		}

		return $object;
	}
}