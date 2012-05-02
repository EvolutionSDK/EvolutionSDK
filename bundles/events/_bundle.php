<?php

namespace Bundles\Events;
use Exception;
use stack;
use e;

class Bundle {
	
	private static $masterEvents = null;
	
	public function __call($event, $args) {
		
		$method = '_on_' . $event;
		$objects = stack::methodObjects($method);
		
		/**
		 * Allow configurable event handling
		 */
		$last = count($args) - 1;
		if(isset($args[$last]) && is_string($args[$last]) && substr($args[$last], 0, 6) === 'allow:') {
			$master = false;
			$file = substr($args[$last], 6);
			array_pop($args);
		} else {
			$master = true;
			$file = e\site . "/configure/master-events.yaml";
		}
		
		if(empty($file))
			throw new Exception('Invalid or no file to control event handling');
		
		$ext = explode('.', $file);
		$ext = end($ext);
		
		$filename = basename($file);
		
		$conf = array();
		if(is_file($file)) {
			switch($ext) {
				case 'json':
					$current = e\decode_file($file);
				break;
				case 'yaml':
					if($master) {
						if(self::$masterEvents === null)
							self::$masterEvents = e::$yaml->load($file, true);
						$current = self::$masterEvents;
					} else {
						$current = e::$yaml->load($file, true);
					}
				break;
				default:
					throw new Exception("Error with file `$filename` the extension `$ext` is not supported.");
				break;
			}
			if(!is_array($current))
				$current = array();
			if(isset($current[$event]) && is_array($current[$event]))
				$conf = $current[$event];
		}
		
		/**
		 * Check if we need to add entries to the file
		 */
		$save = false;
		if($master) {
			
			/**
			 * Use master syntax structure
			 */
			if(!isset($current['events']))
				$current['events'] = array();
			
			if(!isset($current['events'][$event])) {
				$current['events'][$event] = 'enabled';
				$save = true;
			}
			
			foreach($objects as $obj) {
				$class = get_class($obj);
				if(!isset($current['handlers'][$class])) {
					$current['handlers'][$class] = 'enabled';
					$save = true;
				}
			}
			
			if($save = true) {
				self::$masterEvents = $current;
			}
		} else {
			
			/**
			 * Use individual syntax
			 */
			foreach($objects as $obj) {
				$class = get_class($obj);
				if(!isset($current[$event][$class])) {
					$current[$event][$class] = "enabled";
					$save = true;
				}
			}
		}

		// Disable master save
		if($master) $save = false;

		if($save && isset($_GET['--debug'])) {
			switch($ext) {
				case 'json':
					e\encode_file($file, $current);
				break;
				case 'yaml':
					e::$yaml->save($file, $current);
				break;
				default:
					throw new Exception("Error with file `$filename` the extension `$ext` is not supported.");
				break;
			}
		}
		
		e\trace_enter("Running Event <code class='alt'>$event</code>", '', $args, 9);
		
		$results = array();
		
		if($master) {
			/**
			 * Check if the current event is regulated in master-events
			 */
			if($current['events'][$event] !== 'enabled') {
				e\trace('Event <code class="alt2">'.$event.'</code> is disabled', "In file <code>$file</code>", null, 9);
				continue;
			}
		}

		/**
		 * Sort by priority
		 * @todo Cache most event functionality
		 * @author Nate Ferrero
		 */
		$sortCategories = array(
			'first' => array(),
			'unsorted' => array(),
			'last' => array()
		);
		$sortedObjects = array();
		$sort = $method . '_order';
		foreach($objects as $obj) {
			if(property_exists($obj, $sort)) {
				$s = $obj->$sort;

				if(!isset($sortCategories[$s]))
					throw new Exception("Invalid event sort order `$s`");

				$sortCategories[$s][] = $obj;
			} else {
				$sortCategories['unsorted'][] = $obj;
			}
		}

		/**
		 * Debug
		 */
		if(isset($_GET['--events-event']) && $_GET['--events-event'] == $event)
			dump($sortCategories);

		/**
		 * Run all events
		 * @author Nate Ferrero
		 */
		foreach($sortCategories as $category => $objs) {
			foreach($objs as $obj) {
				
				$class = get_class($obj);
				
				if($master) {
					/**
					 * Check if the current handler is enabled in master-events
					 */
					if($current['handlers'][$class] !== 'enabled') {
						e\trace('Event handler <code class="alt2">'.$class.'</code> is disabled', "In file <code>$file</code>", null, 9);
						continue;
					}
				}
				
				/**
				 * Check if the current event is regulated
				 */
				if(isset($current) && isset($current[$event])) {
					
					if(!isset($current[$event][$class]) || $current[$event][$class] !== "enabled") {
						e\trace('Event handler <code class="alt2">'.$class.'</code> is disabled', "In file <code>$file</code>", null, 9);
						continue;
					}
				}
				
				e\trace_enter('Object <code class="alt2">'.$class.'</code> handling event', '', null, 9);
				
				try {
					$results[$class] = call_user_func_array(array($obj, $method), $args);
					e\trace_exit();
				}
				
				catch(Exception $e) {
					e\trace_exception($e);
					
					/**
					 * Trace_exit needs to be here twice, not a typo
					 */
					e\trace_exit();
					e\trace_exit();
					throw $e;
				}
			}
		}
		
		e\trace_exit();
		
		return $results;
		
	}
	
}