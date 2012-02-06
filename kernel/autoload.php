<?php

namespace e;
use Exception;
use stack;
use e;

class AutoLoadException extends Exception {}

function load($class) {

	/* DEBUG * /
	echo "<p>Autoload $class</p>";
	/* END DEBUG */
	
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
	$c = implode('/', $path);
	
	if(empty($c)) {
		return false;
		
		/**
		 * Cant't do this for compatibility with 3rd party code (maybe can check for invalid class bundles\\something)
		 * throw new Exception("The class `$class` does not follow the expected autoload format `bundles\\<i>bundle-name</i>\\<i>some-class</i>`");
		 */
	}
	
	if($c === 'e')
		throw new Exception("You need to put `use e;` at the top of your PHP files, after the namespace definition");
	
	$files = array("$a/$b/$c.php", "$a/$b/library/$c.php");
	$site = e\site;
	$dirs = array(root, $site);
	if(defined('EvolutionBundleLibrary'))
		$dirs[] = \EvolutionBundleLibrary;
	
	if(strtolower($a) == 'bundles' && isset(stack::$bundlePreferences[$b])) {
		if(stack::$bundlePreferences[$b] == 'off')
			throw new Exception("Trying to rely on a bundle that has been turned off in `$site/configure/bundles.txt`");
		else if(stack::$bundlePreferences[$b] == 'core')
			$dirs = array(root);
		else if(stack::$bundlePreferences[$b] == 'site')
			$dirs = array($site);
	}
	
	foreach($dirs as $dir) {
		foreach($files as $pattern) {

			if(defined('EvolutionBundleLibrary') && $dir === \EvolutionBundleLibrary) {
				$pat = explode('/', $pattern, 2);
				if($pat[0] === 'bundles')
					$pattern = $pat[1];
			}

			$pattern = "$dir/".strtolower($pattern);
			foreach(glob($pattern) as $file) {
				require_once($file);
				return;
			}
		}
	}
	
	if(!class_exists($class, false))
		throw new AutoLoadException("Class `$class` not found" . (isset($file) ? " in file `$file`" : ''));
}

spl_autoload_register(__NAMESPACE__.'\load');