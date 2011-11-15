<?php

namespace bundles\environment;
use Exception;
use bundles\SQL\SQLBundle;
use e;

/**
 * Environment Bundle
 */
class Bundle {
	
	private static $in_exception = false;
	private static $environment = null;
	private static $scope = '';
	private static $file;
	private static $url;
	public $active = false;
	
	public function __construct($dir) {
		/**
		 * Load from EvolutionSDK sites folder
		 */
		self::$file = e::$site . '.environment.yaml';
		
		/**
		 * Add to manager
		 */
		e\configure::add('manage.bundle', __NAMESPACE__, 'environment');
	}
	
	public function _on_first_use() {
		self::load();
	}
	
	/**
	 * Require dev mode before page load
	 */
	public function _on_framework_loaded() {
		e::environment()->requireVar('developmentMode', 'yes | no');
	}
	
	/**
	 * Non-static Versions
	 */
	public function invalidVar($var, $ex = null) {
		return $this->_invalid($var, $ex);
	}
	
	public function requireVar($var, $format = '/.+/', $why = 'Not Set', $new = false, $ex = null) {
		return $this->_require($var, $format, $why, $new, $ex);
	}
	
	public function _reset_exception_status() {
		self::$in_exception = false;
	}
	
	/**
	 * Static Versions
	 */
	public function _invalid($var, $ex = null) {
		return $this->_require($var, self::$environment[self::$scope . ".$var---format"], 'is Invalid', true, $ex);
	}
	
	public function _require($var, $format = '/.+/', $why = 'Not Set', $new = false, $ex = null, $addscope = true) {
		
		// If in exception, just return null
		if(self::$in_exception)
			return null;
		
		// Check for var
		$value = '';
		if(isset(self::$environment[$var])) {
			$value = self::$environment[$var];
			if(!$new)
				return $value;
		}
		
		$file = self::$file;
		self::$in_exception = true;
		throw new Exception("<strong>Environment Variable $why:</strong> `$var` in `$file` &rarr; <strong>Required Format:</strong> `$format`", 0, $ex);
	}
	
	public static function getAll() {
		return self::$environment;
	}
	
	public static function load() {
		// Check for file
		if(!is_file(self::$file))
			throw new Exception();
		
		// Load environment file
		self::$environment = e::yaml()->file(self::$file);
	}
}