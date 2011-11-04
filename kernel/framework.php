<?php

class e {
	
	/**
	 * Whether or not the system has been loaded
	 */
	public static $loaded = false;
	
	/**
	 * Contains the matched site
	 */
	public static $site;
	
	/**
	 * Contains the matched site
	 */
	private static $dirs = array();
	
	/**
	 * Load the system core and look for matching site
	 */
	public static function __load($root, $sites, $bundles, $host) {
		
		/**
		 * Discover which site to use
		 */
		foreach(glob("$sites/*/configure/domains.txt") as $file) {
			$domains = file($file);
			array_walk($domains, function(&$v){ $v = trim($v); });
			if(in_array($host, $domains)) {
				self::__load_site($root, dirname(dirname($file)), $bundles);
				return;
			}
		}
		
		/**
		 * No site found
		 */
		throw new Exception("No site matching host `$host` defined in `$sites/<i>site-dir</i>/configure/domains.txt`");
	}
	
	/**
	 * Load all bundles in the core and site
	 */
	public static function __load_site($root, $site, $bundles) {
		
		self::$site = $site;
		
		foreach(array($root.$bundles, $site.$bundles) as $dir) {
			foreach(glob($dir.'/*/*/_bundle.php') as $file)
				self::__load_bundle($file);
		}
		
		/**
		 * Mark system as loaded
		 */
		self::$loaded = true;
		
		/**
		 * Send out an event if events bundle is present
		 */
		if(isset(self::$bundles['events'])) {
			e::events()->framework_loaded();
			e::events()->after_framework_loaded();
		}
	}
	
	/**
	 * Load and cache a bundle
	 */
	public static function __load_bundle($file) {
		require_once($file);
		$dir = dirname($file);
		$bundle = strtolower(basename($dir));
		self::$dirs[$bundle] = $dir;
		$class = "\\bundles\\$bundle\\bundle";
		if(!class_exists($class, false))
			throw new Exception("Bundle class `$class` not found in file `$file`");
		self::$bundles[$bundle] = new $class($dir);
		self::$used[$bundle] = false;
		self::__add_listener(self::$bundles[$bundle]);
	}
	
	/**
	 * Get the bundle directory
	 */
	public static function __bundle_directory($bundle) {
		$bundle = strtolower($bundle);
		if(!isset(self::$dirs[$bundle]))
			throw new Exception("Bundle `$bundle` not installed");
		return self::$dirs[$bundle];
	}
	
	/**
	 * Save bundle methods
	 */
	public static function __add_listener(&$object) {
		foreach(get_class_methods($object) as $method) {
			if(!isset(self::$methods[$method]))
				self::$methods[$method] = array();
			self::$methods[$method][] = &$object;
		}
	}
	
	/**
	 * Get objects for method
	 */
	public static function &__method_objects($method) {
		if(!isset(self::$methods[$method])) {
			$x = array();
			return $x;
		}
		return self::$methods[$method];
	}
	
	/**
	 * Store the bundles and if they have been used
	 */
	private static $bundles = array();
	private static $used = array();
	private static $methods = array();
	
	/**
	 * Return a bundle
	 */
	public static function __callStatic($bundle, $args) {
		/**
		 * Case insensitise the bundle name
		 */
		$bundle = strtolower($bundle);
		
		/**
		 * Enforce system load before bundle use
		 */
		if(!self::$loaded)
			throw new Exception("Cannot use `e::$bundle()` before system has completed loading all
			bundles. Put your functionality in the `_on_first_use` method instead of `__construct`");
		
		/**
		 * Check that bundle exists
		 */
		if(!isset(self::$bundles[$bundle]))
			throw new Exception("The bundle `$bundle` is not installed");
			
		/**
		 * First use
		 */
		if(!self::$used[$bundle]) {
			self::$used[$bundle] = true;
			if(method_exists(self::$bundles[$bundle], '_on_first_use'))
				self::$bundles[$bundle]->_on_first_use();
		}
		
		/**
		 * If bundle has a response, return it
		 */
		if(method_exists(self::$bundles[$bundle], '__bundle_response'))
			return self::$bundles[$bundle]->__bundle_response();
		
		/**
		 * If bundle has an invoke method, call that
		 */
		if(count($args) > 0 && method_exists(self::$bundles[$bundle], '__invoke_bundle'))
			return call_user_func_array(
				array(self::$bundles[$bundle], '__invoke_bundle'), $args);
		
		/**
		 * Return instance
		 */
		return self::$bundles[$bundle];
	}
	
	/**
	 * Map a string to a bundle model
	 */
	public static function map($map) {
		if(preg_match("/^(\w+)\.(\w+):([\w-]+)$/", $map)) {
			
			list($map, $access) = explode(':', $map);
			list($bundle, $model) = explode('.', $map);
			$model = ucwords($model);
			$model = "get$model";
			return e::$bundle()->$model($access);
		}
		else {
			throw new Exception("Trying to load a module with an invalid map format `$map`");
		}
	}
}

/**
 * Dump a single variable
 */
function dump($dump) {
	define('DUMP_SINGLE_VAR', 1);
	eval(d);
}