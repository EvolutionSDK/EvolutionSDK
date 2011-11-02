<?php

namespace Bundles\Deferred;
use Exception;
use bundles\SQL\SQLBundle;
use e;

/**
 * Deferred Bundle
 */
class Bundle {
	
	private $sql;
	private $dir;
	
	public function __construct($dir) {
		$this->dir = $dir;
		$this->sql = new SQLBundle($this->dir);
		e::__add_listener($this->sql);
	}
	
	public function _on_deferred_register() {
		$args = func_get_args();
		$name = array_shift($args);
		if(!is_string($name))
			throw new Exception("No string passed as first argument when running event `deferred_register`");
			
		e::sql()->insert('deferred.pending', array(
			'service' => $name,
			'key' => md5($name . microtime() . rand(0, 100000000)),
			'args' => serialize($args)
		));	
		
		e\trace("<b>Deferred Callback Registered</b>", '', null, 8);
	}
	
	public function _on_after_framework_loaded() {
		e\trace('Processing Deferred Events', '', null, 8);
		$pending = e::sql()->select('deferred.pending')->all();
		
		foreach($pending as $item) {
			
			// Simple access
			extract($item);
			
			// Get arguments
			$args = unserialize($args);
			if(!is_array($args))
				throw new Exception("Unserialize failed on deferred service `$service`");
			
			// Run the service
			call_user_func_array(array(e::events(), $service), $args);
			
			// If no errors, we can remove this from the table
			e::sql()->query("DELETE FROM `deferred.pending` WHERE `key` = '$key'");
		}
	}
}