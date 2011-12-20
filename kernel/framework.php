<?php

class stack {
		
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
	 * Store the bundles and if they have been used
	 */
	public static $bundles = array();
	public static $_bundle_initialized = array();
	private static $methods = array();
	
	/**
	 * Load the system core and look for matching site
	 */
	public static function __load($root, $sites, $bundles, $host) {
		

		e\trace('EvolutionSDK Framework', "Looking for sites matching host `$host`");
		
		/**
		 * Look for domains
		 */
		$domainFiles = "$sites/*/configure/domains.txt";
		
		/**
		 * Discover which site to use
		 */
		foreach(glob($domainFiles) as $file) {
			$domains = file($file);
			array_walk($domains, function(&$v){ $v = trim($v); });
			foreach($domains as $domain) {
				if(preg_match('/^'.str_replace('*', '.+', str_replace('.', '\\.', $domain)).'$/', $host)) {
					self::__load_site($root, dirname(dirname($file)), $bundles);
					return;
				}
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
		
		e\trace_enter('EvolutionSDK Framework', "Loading site `$site`");
		
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
			e::$events->framework_loaded();
			e::$events->after_framework_loaded();
		}
		
		e\trace_exit();
	}
	
	/**
	 * Load and cache a bundle
	 */
	public static function __load_bundle($file) {
		
		$dir = dirname($file);
		$bundle = strtolower(basename($dir));
		
		e\trace_enter('EvolutionSDK Framework', "Loading bundle `$bundle`", array($file), 9);
		
		require_once($file);
		self::$dirs[$bundle] = $dir;
		$class = "\\bundles\\$bundle\\bundle";
		if(!class_exists($class, false))
			throw new Exception("Bundle class `$class` not found in file `$file`");
		self::$bundles[$bundle] = new $class($dir);
		self::$_bundle_initialized[$bundle] = false;
		self::__add_listener(self::$bundles[$bundle]);
		
		e\trace_exit();
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
	 * Return a bundle
	 */
	public static function __callBundle($bundle, $args) {
		/**
		 * Case insensitise the bundle name
		 */
		$bundle = strtolower($bundle);
		
		/**
		 * Enforce system load before bundle use
		 */
		if(!self::$loaded)
			throw new Exception("Cannot use `e::$bundle()` before system has completed loading all
			bundles. Put your functionality in the `__initBundle` method instead of `__construct`");
		
		/**
		 * Check that bundle exists
		 */
		if(!isset(self::$bundles[$bundle]))
			throw new Exception("The bundle `$bundle` is not installed");
			
		/**
		 * First use
		 */
		if(!self::$_bundle_initialized[$bundle]) {
			self::$_bundle_initialized[$bundle] = true;
			if(method_exists(self::$bundles[$bundle], '__initBundle'))
				self::$bundles[$bundle]->__initBundle();
		}
		
		/**
		 * If bundle has a response, return it
		 */
		if(method_exists(self::$bundles[$bundle], '__callBundle'))
			return call_user_func_array(
				array(self::$bundles[$bundle], '__callBundle'), $args);
		
		/**
		 * Use static access
		 */
		throw new Exception("The bundle `$bundle` cannot be accessed as a function,
			you must use `e::$$bundle` static var access instead.");
		
		/**
		 * Return instance
		 */
		return self::$bundles[$bundle];
	}
}

/**
 * HACK: e_var_access
 * Allow bundle access by static variable before PHP6
 */
require_once(__DIR__ . '/hacks/e_var_access.php');
require_once(__DIR__ . '/hacks/e_var_access_generated.php');
class e extends e_var_access {
	
	/**
	 * Load a bundle
	 */
	public static function __callStatic($bundle, $args) {
		return stack::__callBundle($bundle, $args);
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
 * HACK
 */
if(function_exists('e_static_bundle_access_init'))
	e_static_bundle_access_init();

/**
 * Dump a single variable
 */
function dump($dump) {
	define('DUMP_SINGLE_VAR', 1);
	eval(d);
}