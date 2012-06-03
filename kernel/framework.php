<?php

class MapException extends Exception {}

class Stack {
		
	/**
	 * Whether or not the system has been loaded
	 * @author Nate Ferrero
	 */
	public static $loaded = false;

	/**
	 * Bundle location (and potentially other) preferences
	 * @author Nate Ferrero
	 */
	public static $bundlePreferences = array();
	
	/**
	 * Store the bundles and if they have been used
	 * @author Nate Ferrero
	 */

	public static $dirs = array();
	public static $bundles = array();
	public static $bundleInitialized = array();
	private static $bundleLocations = array();
	private static $methods = array();
	
	/**
	 * Load all bundles in the core and site
	 * @author Nate Ferrero
	 */
	public static function loadSite($root, $site, $bundles) {
		
		e\trace_enter('EvolutionSDK Framework', "Loading site `$site`");

		/**
		 * Check for bundle status
		 * @author Nate Ferrero
		 */
		if(substr($_SERVER['REQUEST_URI'], 0, 11) === '/@--status-') {
			$bundle = substr($_SERVER['REQUEST_URI'], 11);
			echo e\bundle_status($bundle);
			e\complete();
		}
		
		/**
		 * Load site bundles
		 * @author Nate Ferrero
		 */
		foreach(glob($site.$bundles.'/*/_bundle.php') as $file) {
			self::loadBundle($file, 'site');
		}

		/**
		 * Get bundle libraries
		 * ====================
		 *    04 - 06 - 2012
		 * ====================
		 * @author Kelly Becker
		 */
		$constants = get_defined_constants(true);
		$EBL = array_filter(array_flip($constants['user']), function($key) {
			if(strpos($key, 'EvolutionBundleLibrary') === false)
				return false;
			else return true;
		});

		/**
		 * Load bundles
		 * ==============
		 * 04 - 06 - 2012
		 * ==============
		 * @author Kelly Becker
		 */
		foreach(array_flip($EBL) as $EBLD) foreach(new \DirectoryIterator($EBLD) as $file) {
			if(strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'phar')
				$file = 'phar://'.$file->getPath().'/'.$file.'/_bundle.php';
			else $file = $file->getPath().'/'.$file.'/_bundle.php';

			if(is_file($file)) self::loadBundle($file, 'library');
		}

		/**
		 * Load core bundles
		 * @author Kelly Becker
		 */
		foreach(new \DirectoryIterator($root.$bundles) as $file) {
			if(strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'phar')
				$file = 'phar://'.$file->getPath().'/'.$file.'/_bundle.php';
			else $file = $file->getPath().'/'.$file.'/_bundle.php';

			if(is_file($file)) self::loadBundle($file, 'core');
		}

		/**
		 * Mark system as loaded
		 */
		self::$loaded = true;

		/**
		 * Run EvolutionSDK Commands
		 * @author Nate Ferrero
		 */
		if(substr($_SERVER['REQUEST_URI'], 0, 4) === '/@--') {
			$init = substr($_SERVER['REQUEST_URI'], 4);
			$command = preg_replace('/[^a-z0-9\-]/', '', $init);
			if($command !== $init)
				throw new Exception('Invalid command syntax');
			$command = e\root . "/commands/$command.php";
			if(file_exists($command))
				require_once($command);
			else
				throw new Exception("Command `$command` not found");
		}
		
		/**
		 * Send out some events if events bundle is present
	 	 * @author Nate Ferrero
		 */
		if(isset(e::$events)) {
			e::$events->framework_security();
			e::$events->framework_database();
			e::$events->framework_loaded();
			e::$events->after_framework_loaded();
		}
		
		e\trace_exit();
	}
	
	/**
	 * Load and cache a bundle
	 * @author Nate Ferrero
	 */
	public static function loadBundle($file, $location = 'other') {
		
		/**
		 * Get the directory name
		 */
		$dir = dirname($file);

		/**
		 * Is this Bundle is in a Phar
		 * @author Kelly Becker
		 */
		if(strtolower(pathinfo(basename($dir), PATHINFO_EXTENSION)) === 'phar') {

			/**
			 * If it is within a phar and no _bundle file exists error
			 */
			if(!is_file($file))
				throw new Exception("The Phar bundle must contain a _bundle.php");

			/**
			 * Get the bundle name
			 */
			$bundle = strtolower(pathinfo(basename($dir), PATHINFO_FILENAME));
		}

		/**
		 * Get the bundle name by the folder name
		 */
		else $bundle = strtolower(basename($dir));
		$site = e\site;
		
		if(!isset(self::$bundleLocations[$bundle]))
			self::$bundleLocations[$bundle] = $dir;
		else {
			$old = self::$bundleLocations[$bundle];
			e\trace('EvolutionSDK Framework', "Bundle `$bundle` has already been loaded from `$old`", array($file), 9);
			return;
		}

		e\trace_enter('EvolutionSDK Framework', "Loading bundle `$bundle`", array($file), 9);
		
		/**
		 * Load the bundle
		 */
		require_once($file);
		self::$dirs[$bundle] = $dir;
		$class = "\\bundles\\$bundle\\bundle";
		if(!class_exists($class, false))
			throw new Exception("Bundle class `$class` not found in file `$file`");
		self::$bundles[$bundle] = new $class($dir);
		self::$bundleInitialized[$bundle] = false;
		self::addListener(self::$bundles[$bundle]);
		
		/**
		 * Load bundle extensions
		 * @author Kelly Becker: Changed to use DirectoryIterator for speed and Phar Support
		 * @author Nate Ferrero
		 */
		if(is_dir($dir . '/extend')) {
			foreach(new \DirectoryIterator($dir . '/extend') as $bundle) {
				if(strpos($bundle, '.') === 0 || strlen($bundle) < 1) continue;

				$bundle = $bundle->getPathname();
				e\extend(basename($bundle), dirname($bundle));
			}
		}

		e\trace_exit();
	}
	
	/**
	 * Get the bundle directory
	 * @author Nate Ferrero
	 */
	public static function bundleDirectory($bundle) {
		$bundle = strtolower($bundle);
		if(!isset(self::$dirs[$bundle]))
			throw new Exception("Bundle `$bundle` not installed");
		return self::$dirs[$bundle];
	}
	
	/**
	 * Save bundle methods
	 * @author Nate Ferrero
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
	 * @author Nate Ferrero
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
	 * @author Nate Ferrero
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
	public static function bundleLocations($which = null) {
		return empty($which) ? self::$bundleLocations : (
			isset(self::$bundleLocations[$which]) ? self::$bundleLocations[$which] : null
		);
	}
}

/**
 * HACK: e_var_access
 * Allow bundle access by static variable before PHP 6
 * @todo Remove this in PHP 6
 * @author Nate Ferrero
 */
require_once(__DIR__ . '/hacks/e_var_access.php');
require_once(e\siteCache . '/e_var_access_generated.php');
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
	public static function map($map, $validate = false) {
		if(preg_match("/^(\w+)\.(\w+):([\w-]+)$/", $map)) {
			if($validate) return $map;
			
			list($map, $access) = explode(':', $map);
			list($bundle, $model) = explode('.', $map);
			$model = ucwords($model);
			$model = "get$model";
				
			if(!isset(e::$$bundle))
				throw new MapException("Bundle `$bundle` is not installed");
			
			return e::$$bundle->$model($access);
		}
		else {
			throw new MapException("Trying to load a module with an invalid map format `$map`");
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

	/**
	 * Allow Dumping JSON
	 * @author Kelly Becker
	 */
	if(isset($_GET['--dump-json'])) {
		header("Content-Type: application/json");
		echo json_encode($dump);
		die;
	}

	define('DUMP_SINGLE_VAR', 1);
	eval(d);
}

/**
 * Dump a single variable formatted as plain text
 * @author Nate Ferrero
 */
function dumpt($dump) {
	define('DUMP_SINGLE_VAR', 1);
	define('DUMP_FORMAT_TEXT', 1);
	eval(d);
}