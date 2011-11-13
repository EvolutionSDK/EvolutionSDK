<?php

namespace e;
use Exception;
use e;

class AutoLoadException extends Exception {}

function load($class) {
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
	
	$files = array("$a/*/$b/$c.php", "$a/*/$b/library/$c.php");
	
	$dirs = array(root, e::$site);
	
	foreach($dirs as $dir) {
		foreach($files as $pattern) {
			$pattern = "$dir/".strtolower($pattern);
			foreach(glob($pattern) as $file) {
				require_once($file);
				/**
				 * TODO Why can't we return here if we just included a file?
				 */
			}
		}
	}
	
	if(!class_exists($class, false))
		throw new AutoLoadException("Class `$class` not found" . (isset($file) ? " in file `$file`" : ''));
}

spl_autoload_register(__NAMESPACE__.'\load');