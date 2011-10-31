<?php

namespace e;
use Exception;
use e;

class AutoLoadException extends Exception {}

function load($class) {
	$path = explode('\\', $class);
	if($path[0] == '')
		array_shift($path);
	
	$a = array_shift($path);
	$b = array_shift($path);
	$c = array_shift($path);
	
	if(empty($c))
		throw new Exception("The class `$class` does not follow the expected autoload format `bundles\\<i>bundle-name</i>\\<i>some-class</i>`");
	
	if($c === 'e')
		throw new Exception("You need to put `use e;` at the top of your PHP files, after the namespace definition");
	
	$files = array("$a/*/$b/$c.php", "$a/*/$b/library/$c.php");
	
	$dirs = array(root, e::$site);
	
	foreach($dirs as $dir) {
		foreach($files as $pattern) {
			$pattern = "$dir/".strtolower($pattern);
			foreach(glob($pattern) as $file)
				require_once($file);
		}
	}
	
	if(!class_exists($class, false))
		throw new AutoLoadException("Class `$class` not found" . (isset($file) ? " in file `$file`" : ''));
}

spl_autoload_register(__NAMESPACE__.'\load');