<?php

class Stack {
		
	/**
	 * Whether or not the system has been loaded
	 */
	public static $loaded = false;

	/**
	 * Bundle location (and potentially other) preferences
	 */
	public static $bundlePreferences = array();
	
	/**
	 * Store the bundles and if they have been used
	 */
	public static $dirs = array();
	public static $bundles = array();
	public static $bundleInitialized = array();
	private static $bundleLocations = array();
	private static $methods = array();
	
	/**
	 * Load all bundles in the core and site
	 */
	public static function loadSite($root, $site, $bundles) {
		
		e\trace_enter('EvolutionSDK Framework', "Loading site `$site`");
		
		/**
		 * Load site bundles
		 */
		foreach(glob($site.$bundles.'/*/_bundle.php') as $file) {
			self::loadBundle($file, 'site');
		}

		/**
		 * Load core bundles
		 */
		foreach(glob($root.$bundles.'/*/_bundle.php') as $file) {
			self::loadBundle($file, 'core');
		}
		
		/**
		 * Mark system as loaded
		 */
		self::$loaded = true;
		
		/**
		 * Send out an event if events bundle is present
		 */
		if(isset(self::$bundles['events'])) {
			e::$events->framework_security();
			e::$events->framework_loaded();
			e::$events->after_framework_loaded();
		}
		
		e\trace_exit();
	}
	
	/**
	 * Load and cache a bundle
	 */
	public static function loadBundle($file, $location = 'other') {
		
		$dir = dirname($file);
		$bundle = strtolower(basename($dir));
		$site = e\site;
		
		if(!isset(self::$bundleLocations[$bundle]))
			self::$bundleLocations[$bundle] = $dir;
		else {
			$old = self::$bundleLocations[$bundle];
			e\trace('EvolutionSDK Framework', "Bundle `$bundle` has already been loaded from `$old`", array($file), 9);
			return;
		}

		e\trace_enter('EvolutionSDK Framework', "Loading bundle `$bundle`", array($file), 9);
		
		require_once($file);
		self::$dirs[$bundle] = $dir;
		$class = "\\bundles\\$bundle\\bundle";
		if(!class_exists($class, false))
			throw new Exception("Bundle class `$class` not found in file `$file`");
		self::$bundles[$bundle] = new $class($dir);
		self::$bundleInitialized[$bundle] = false;
		self::addListener(self::$bundles[$bundle]);
		
		e\trace_exit();
	}
	
	/**
	 * Get the bundle directory
	 */
	public static function bundleDirectory($bundle) {
		$bundle = strtolower($bundle);
		if(!isset(self::$dirs[$bundle]))
			throw new Exception("Bundle `$bundle` not installed");
		return self::$dirs[$bundle];
	}
	
	/**
	 * Save bundle methods
	 */
	public static function addListener(&$object) {
		foreach(get_class_methods($object) as $method) {
			if(!isset(self::$methods[$method]))
				self::$methods[$method] = array();
			self::$methods[$method][] = &$object;
		}
	}
	
	/**
	 * Get objects for method
	 */
	public static function &methodObjects($method) {
		if(!isset(self::$methods[$method])) {
			$x = array();
			return $x;
		}
		return self::$methods[$method];
	}
	
	/**
	 * Return a bundle
	 */
	public static function bundle($bundle, $args) {
		/**
		 * Bundle names should be case insensitive
		 */
		$bundle = strtolower($bundle);
		
		/**
		 * Enforce system load before bundle use
		 */
		if(!self::$loaded)
			throw new Exception("Cannot use `e::$$bundle` before system has completed loading all
			bundles. Put your functionality in the `__initBundle` method instead of `__construct`");
		
		/**
		 * Check that bundle exists
		 */
		if(!isset(self::$bundles[$bundle]))
			throw new Exception("The bundle `$bundle` is not installed");
			
		/**
		 * First use
		 */
		if(!self::$bundleInitialized[$bundle]) {
			self::$bundleInitialized[$bundle] = true;
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
	
	/**
	 * Bundle locations
	 * @author Nate Ferrero
	 */
	public function bundleLocations() {
		return self::$bundleLocations;
	}
}

/**
 * HACK: e_var_access
 * Allow bundle access by static variable before PHP 6
 * @author Nate Ferrero
 */
require_once(__DIR__ . '/hacks/e_var_access.php');
require_once(e\root . '/cache/' . basename(e\site) . '/e_var_access_generated.php');
class e extends e_var_access {
	
	/**
	 * Load a bundle
	 * @author Nate Ferrero
	 */
	public static function __callStatic($bundle, $args) {
		return Stack::bundle($bundle, $args);
	}
	
	/**
	 * Map a string to a bundle model
	 * @author David Boskovic
	 */
	public static function map($map) {
		if(preg_match("/^(\w+)\.(\w+):([\w-]+)$/", $map)) {
			
			list($map, $access) = explode(':', $map);
			list($bundle, $model) = explode('.', $map);
			$model = ucwords($model);
			$model = "get$model";
				
			if(!isset(e::$$bundle))
				throw new Exception("Bundle `$bundle` is not installed");
			
			return e::$$bundle->$model($access);
		}
		else {
			throw new Exception("Trying to load a module with an invalid map format `$map`");
		}
	}
}

/**
 * HACK
 * @todo in PHP 6 remove this
 * @author Nate Ferrero
 */
if(function_exists('e_static_bundle_access_init'))
	e_static_bundle_access_init();

/**
 * Dump a single variable
 * @author Nate Ferrero
 */
function dump($dump) {
	define('DUMP_SINGLE_VAR', 1);
	eval(d);
}