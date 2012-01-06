<?php

namespace bundles\environment;
use Exception;
use bundles\SQL\SQLBundle;
use stack;
use e;

/**
 * Environment Bundle
 */
class Bundle {
	
	private static $in_exception = false;
	private static $environment = array();
	private static $scope = '';
	private static $file;
	private static $url;
	public $active = false;
	
	public function __construct($dir) {
		/**
		 * Load from EvolutionSDK sites folder
		 */
		self::$file = e\site . '/configure/environment.yaml';
	}
	
	public function __initBundle() {
		self::load();
	}
	
	/**
	 * Require dev mode before page load
	 */
	public function _on_framework_loaded() {
		
		/**
		 * Add to manager
		 */
		e::configure('manage')->activeAddKey('bundle', __NAMESPACE__, 'environment');
		
		/**
		 * Check dev mode to avoid issues later
		 */
		e::$environment->requireVar('DevelopmentMode', 'yes | no');
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
	
	public function getVar($var, $format = '/.+/', $why = 'Not Set', $new = false, $ex = null) {
		return $this->_require($var, $format, $why, $new, $ex, false);
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
	
	public function _require($var, $format = '/.+/', $why = 'Not Set', $new = false, $ex = null, $throw = true) {
		
		// Make active to prevent exceptions
		$this->active = true;
		
		// If in exception, just return null
		if(self::$in_exception)
			return null;
		
		// Check for var
		$orig = $var;
		$var = strtolower($var);
		$value = '';
		if(isset(self::$environment[$var])) {
			$value = self::$environment[$var];
			if(!$new)
				return $value;
		}
		
		$file = self::$file;
		self::$in_exception = true;
		if($throw) throw new Exception("<strong>Environment Variable $why:</strong> `$orig` in `$file` &rarr; <strong>Required Format:</strong> `$format`", 0, $ex);
		
		else return null;
	}
	
	public static function getAll() {
		return self::$environment;
	}
	
	public static function load() {
		// Check for file
		if(!is_file(self::$file))
			throw new Exception();
		
		// Load environment file
		$tmp = e::$yaml->file(self::$file);
		foreach($tmp as $key => $value)
			self::$environment[strtolower($key)] = $value;
	}
}