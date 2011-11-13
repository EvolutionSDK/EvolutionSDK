<?php

namespace Bundles\Action;
use Exception;
use e;

class Bundle {
	
	private static $actions = array();
	
	public function __construct() {
		e\configure::add('action.class-format', '\\Action\\%');
		e\configure::add('action.location', e::$site);
	}
	
	public function _on_portal_route($path, $dir) {
		$this->route($path, array($dir));
	}
	
	public function _on_router_route($path) {
		$this->route($path, e\configure::getArray('action.location'));
	}
	
	public static function route($path, $dirs = null) {
		
		// If dirs are not specified, use defaults
		if(is_null($dirs))
			$dirs = e\configure::getArray('action.location');
		
		// Make sure path contains valid action name
		if(!isset($path[0]) || $path[0] == '')
			return;
		
		// Get the action name
		$name = strtolower($path[0]);
		
		// Check all dirs for a matching action
		foreach($dirs as $dir) {
			// Look in action folder
			if(basename($dir) !== 'actions')
				$dir .= '/actions';
			
			// Skip if missing
			if(!is_dir($dir))
				continue;
				
			// File to check
			$file = "$dir/$name.php";
			
			// Skip if incorrect file
			if(!is_file($file))
				continue;
			
			// Load action if not already loaded
			if(!isset(self::$actions[$file])) {
				
				// Require the controller
				require_once($file);
				
				// action class
				$classFormats = e\configure::getArray('action.class-format');
				
				// Check each class format
				$found = false;
				foreach($classFormats as $format) {
					
					// Format class with action name
					$class = str_replace("%", $name, $format);
					
					// Check if this is a valid class
					if(class_exists($class, false)) {
						$found = true;
						break;
					}
				}
				
				// Maybe we just ran out of formats to check
				if(!$found) {
					$classes = implode('`, `', $classFormats);
					$classes = str_replace('%', $name, $classes);
					throw new Exception("None of the possible action classes: `$classes` are defined in `$file`");
				}
				
				// Load action
				self::$actions[$file] = new $class;
			}
            
            // Complete the current binding queue
            e\Complete();
		}
	}
}