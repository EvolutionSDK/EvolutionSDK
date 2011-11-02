<?php

namespace bundles\events;
use Exception;
use e;

class Bundle {
	
	public function __call($event, $args) {
		
		$method = '_on_' . $event;
		$objects = e::__method_objects($method);
		
		/**
		 * Allow configurable event handling
		 */
		$last = count($args) - 1;
		if(isset($args[$last])) {
			$last = $args[$last];
			if(is_string($last) && substr($last, 0, 6) === 'allow:') {
				array_pop($args);
				
				$file = substr($last, 6);
				
				$ext = explode('.',$file);
				$ext = end($ext);
				
				$filename = basename($file);
				
				$conf = array();
				if(is_file($file)) {
					switch($ext) {
						case 'json':
							$current = e\decode_file($file);
						break;
						case 'yaml':
							$current = e::yaml()->load($file, true);
						break;
						default:
							throw new Exception("Error with `$filename` the config extension `$ext` is not supported.");
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
				foreach($objects as $obj) {
					$class = get_class($obj);
					if(!isset($current[$event][$class])) {
						$current[$event][$class] = "disabled";
						$save = true;
					}
				}
				
				if($save) {
					switch($ext) {
						case 'json':
							e\encode_file($file, $current);
						break;
						case 'yaml':
							e::yaml()->save($file, $current);
						break;
						default:
							throw new Exception("Error with `$filename` the config extension `$ext` is not supported.");
						break;
					}
				}
			}
		}
		
		e\trace_enter("Running Event <code class='alt'>$event</code>", '', $args);
		
		$results = array();

		foreach($objects as &$obj) {
			
			$class = get_class($obj);
						
			/**
			 * Check if the current event is regulated
			 */
			if(isset($current) && isset($current[$event])) {
				
				if(!isset($current[$event][$class]) || $current[$event][$class] !== "enabled") {
					e\trace('Event handler <code class="alt2">'.$class.'</code> is disabled', "In file <code>$file</code>");
					continue;
				}
			}
			
			e\trace_enter('Object <code class="alt2">'.$class.'</code> handling event');
			
			try {
				$results[] = call_user_func_array(array($obj, $method), $args);
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
		
		e\trace_exit();
		
		return $results;
		
	}
	
}