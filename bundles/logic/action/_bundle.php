<?php

namespace Bundles\Action;
use Exception;
use stack;
use e;

class Bundle {
	
	private static $actions = array();
	
	public function _on_portal_route($path, $dir) {
		$this->route($path, array($dir));
	}
	
	public function _on_router_route($path) {
		$this->route($path, e::configure('action')->locations);
	}
	
	public function __callBundle($action) {
		if($action) {
			$action = str_replace('.','\\', $action);
			$r = $this->load(array(\Bundles\Portal\Bundle::$currentPortalName, $action), true);
			return $r;
		}
		
		return $this;
	}
	
	public function load($action, $data) {
		if(is_array($action)) {
			$dirs = array(stack::$site.'/portals/'.array_shift($action));
			$name = str_replace('\\', '/', strtolower(array_shift($action)));
		}
		else $name = str_replace('\\', '/', strtolower($action));
		
		// Add defaults
		e::configure('action')->activeAdd('class_format', '\\Action\\%');
		e::configure('action')->activeAdd('locations', stack::$site);
		
		// If dirs are not specified, use defaults
		if(!isset($dirs))
			$dirs = e::configure('action')->locations;
		
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
			
			// Require the controller
			require_once($file);
				
			// action class
			$classFormats = e::configure('action')->class_format;
			
			// Check each class format
			$found = false;
			foreach($classFormats as $format) {
				
				// Format class with action name
				$class = str_replace(array('%', '/'), array($name, '\\'), $format);
				
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
			return new $class($data);
			
		}
		
	}
	
	public static function route($path, $dirs = null) {
		
		// Add defaults
		e::configure('action')->activeAdd('class_format', '\\Action\\%');
		e::configure('action')->activeAdd('locations', stack::$site);
		
		// If dirs are not specified, use defaults
		if(is_null($dirs))
			$dirs = e::configure('action')->locations;
			
		// Make sure path contains valid action name
		if(!isset($path[0]) || $path[0] !== 'do')
			return;
			
		/**
		 * Take off the /do
		 */
		array_shift($path);
		
		// Get the action name
		$name = strtolower(implode('/',$path));
		
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
				$classFormats = e::configure('action')->class_format;
				
				// Check each class format
				$found = false;
				foreach($classFormats as $format) {
					
					// Format class with action name
					$class = str_replace(array('%', '/'), array($name, '\\'), $format);
					
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