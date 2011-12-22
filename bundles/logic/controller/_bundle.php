<?php

namespace Bundles\Controller;
use Exception;
use stack;
use e;

class Bundle {
	
	private static $controllers = array();
	
	public function _on_portal_route($path, $dir) {
		$this->route($path, array($dir));
	}
	
	public function _on_router_route($path) {
		$this->route($path, e::configure('controller')->locations);
	}
	
	public function route($path, $dirs) {
		// Add defaults
		e::configure('controller')->activeAdd('class_format', '\\Controller\\%');
		e::configure('controller')->activeAdd('locations', stack::$site);
		e::configure('autoload')->activeAddKey('special', 'Controller\\FormController', __DIR__ . '/library/form-controller.php');
		
		// If dirs are not specified, use defaults
		if(is_null($dirs))
			$dirs = e::configure('controller')->locations;
		
		// Get the action name
		$name = strtolower(implode('/',$path));
		
		/**
		 * Check all dirs for a matching controller
		 */
		if(is_array($dirs) || $dirs instanceof \Traversable) foreach($dirs as $dir) {
			
			/**
			 * Look in controllers folder
			 */
			if(basename($dir) !== 'controllers')
				$dir .= '/controllers';

			/**
			 * Skip if missing
			 */
			if(!is_dir($dir))
				continue;

			/**
			 * Find File
			 */
			$args = array();
			$filea = explode('/', $name);
			$total = count($filea);
			$i = 0;
			while($i <= $total) {
				if(is_file($file = $dir.'/'.($name = implode('/', $filea)).'.php'))
					break;
					
				$args[] = array_pop($filea);
				$i++;
			}
			
			/**
			 * Skip if incorrect file
			 */
			if(!is_file($file))
				continue;
				
			$fname = basename($file);
				
			/**
			 * Trace
			 */
			e\trace(__CLASS__, "Matched controller `$fname`");

			/**
			 * Load controller if not already loaded
			 */
			if(!isset(self::$controllers[$file])) {

				/**
				 * Require the controller
				 */
				require_once($file);

				/**
				 * Controller class
				 */
				$classFormats = e::configure('controller')->class_format;

				/**
				 * Check each class format
				 */
				$found = false;
				foreach($classFormats as $format) {

					/**
					 * Format class with controller name
					 */
					$class = str_replace(array('%', '/'), array($name, '\\'), $format);

					/**
					 * Check if this is a valid class
					 */
					if(class_exists($class, false)) {
						$found = true;
						break;
					}
				}

				/**
				 * Maybe we just ran out of formats to check
				 */
				if(!$found) {
					$classes = implode('`, `', $classFormats);
					$classes = str_replace('%', $name, $classes);
					throw new Exception("None of the possible controller classes: `$classes` are defined in `$file`");
				}

				/**
				 * Load controller
				 */
				self::$controllers[$file] = new $class;
			}
			
			/**
			 * Get the method name
			 */
			$method = !empty($args) ? array_pop($args) : 'index';

			/**
			 * make sure that our controller method exists before attempting to call it
			 */
			if(!method_exists(self::$controllers[$file],$method))
				   throw new Exception("Controller `$name` exists but the method `$method` is not defined in `$file`");

			/**
		 	 * Call the appropriate controller method with the remaining path elements as arguments
			 */
			$result = call_user_func_array(
				array(self::$controllers[$file], $method),
				$path
			);
			
			/**
			 * If the controller has a complete method
			 */
			if(method_exists(self::$controllers[$file],'_complete'))
				self::$controllers[$file]->_complete();
			
			/**
			 * Complete the page load
			 */
			e\complete($result);
		}
	}
}