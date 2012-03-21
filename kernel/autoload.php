<?php

namespace e;
use Exception;
use stack;
use e;

class AutoLoadException extends Exception {
	public $severity = 5;
}

function VerifyClass(&$class) {
	$test = explode('\\', $class);
	$base = array_pop($test);
	$test = implode('\\', $test) . '\\_' . $base;
	try {
		if(class_exists($class))
			return true;
	} catch(AutoLoadException $e) {}
	try {
		if(class_exists($test))
			$class = $test;
		return true;
	} catch(AutoLoadException $e) {
		return false;
	}
}

/**
 * Allow extending the autoloader
 * @author Nate Ferrero
 */
function extend($bundle, $dir = null) {
	static $extensions;

	if($dir == null)
		return isset($extensions[$bundle]) ? $extensions[$bundle] : array();

	if(!isset($extensions[$bundle]))
		$extensions[$bundle] = array();

	$extensions[$bundle][] = $dir;
}

/**
 * This is the main autoloader
 * @author Nate Ferrero
 */
function load($class) {

	/* DEBUG * /
	echo "<p>Autoload $class</p>";
	/* END DEBUG */
	
	/**
	 * Special autoload overrides
	 * @author Nate Ferrero
	 */
	if(stack::$loaded) {
		$raw = e::configure('autoload', false)->activeGet('special');
		if(!is_null($raw)) {
			$special = array();
			foreach($raw as $key => $value) {
				$special[strtolower($key)] = $value;
			}
			$class = strtolower($class);
			if(isset($special[$class])) {
				require_once($special[$class]);
				return true;
			}
		}
	}
	
	$path = explode('\\', strtolower($class));
	if($path[0] == '')
		array_shift($path);
	
	$a = array_shift($path);
	$b = array_shift($path);
	if(count($path))
		$base = $path[count($path) - 1];
	else
		$base = '';
	$c = implode('/', $path);
	
	if(empty($c)) {
		return false;
		
		/**
		 * Cant't do this for compatibility with 3rd party code (maybe can check for invalid class bundles\\something)
		 * throw new Exception("The class `$class` does not follow the expected autoload format `bundles\\<i>bundle-name</i>\\<i>some-class</i>`");
		 */
	}
	
	if($base === 'e')
		throw new Exception("You need to put `use e;` at the top of your PHP files, after the namespace definition.");

	/**
	 * Handle reserved names in classes by allowing _className
	 * @author Nate Ferrero
	 * @author Kelly Becker
	 */
	if($base[0] === '_') {
		$base = substr($base, 1);
		array_pop($path);
		array_push($path, $base);
		$c = implode('/', $path);
	}

	$files = array("$a/$b/$c.php", "$a/$b/library/$c.php");
	$bfiles = array("$b/$c.php", "$b/library/$c.php");
	$site = e\site;
	$dirs = array(root, $site);

	/**
	 * Special handling for bundle files
	 * @author Nate Ferrero
	 */
	if(strtolower($a) === 'bundles') {

		if(defined('EvolutionBundleLibrary'))
			$dirs[] = '@'.\EvolutionBundleLibrary;
	
		if(isset(stack::$bundlePreferences[$b])) {
			if(stack::$bundlePreferences[$b] == 'off')
				throw new Exception("Trying to rely on a bundle that has been turned off in `$site/configure/bundles.txt`");
			else if(stack::$bundlePreferences[$b] == 'core')
				$dirs = array(root);
			else if(stack::$bundlePreferences[$b] == 'site')
				$dirs = array($site);
		}

		/**
		 * Check for bundle extension folders
		 * @author Nate Ferrero
		 */
		$extensions = extend($b);
		if(is_array($extensions)) {
			foreach($extensions as $extension) {
				array_unshift($dirs, '@'.$extension);
			}
		}
	}
	
	foreach($dirs as $dir) {

		/**
		 * "@/some/directory" means that the bundles folder is that directory, rather than looking for a 
		 * /bundles directory within that directory
		 * @author Nate Ferrero
		 */
		if($dir[0] === '@') {
			$xfiles = $bfiles;
			$dir = substr($dir, 1);
		} else {
			$xfiles = $files;
		}

		/**
		 * Search for specified files
		 */
		foreach($xfiles as $pattern) {

			$pattern = "$dir/".strtolower($pattern);

			/* DEBUG * /
			echo "<p>Class <code>$class</code> could be in <code>$pattern</code></p>";
			/* END */

			if(file_exists($pattern)) {
				require_once($pattern);

				/* DEBUG * /
				echo "<p>It was!</p>";
				/* END */

				return;
			}
		}
	}
	
	if(!class_exists($class, false))
		throw new AutoLoadException("Class `$class` not found" . (isset($file) ? " in file `$file`" : ''));
}

spl_autoload_register(__NAMESPACE__.'\load');