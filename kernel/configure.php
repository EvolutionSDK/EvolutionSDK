<?php

namespace e;

/**
 * Evolution Configure
 * Configure sets basic options for any bundle
 * @author Nate Ferrero
 */
class configure {
	
	private static $configuration = array();
	
	public static function set($name, $value) {
		self::$configuration[$name] = $value;
	}
	
	public static function add($name, $value_or_key, $value = '__no_key_being_used') {
		if($value == '__no_key_being_used') $value = $value_or_key;
		else $key = $value_or_key;
		if(isset($key))
			self::$configuration[$name][$key] = $value;
		else
			self::$configuration[$name][] = $value;
	}
	
	public static function get($name) {
		return isset(self::$configuration[$name]) ? self::$configuration[$name] : null;
	}
	
	public static function getArray($name) {
		$x = self::get($name);
		if(is_array($x))
			return $x;
		if(is_null($x))
			return array();
		return array($x);
	}
	
}