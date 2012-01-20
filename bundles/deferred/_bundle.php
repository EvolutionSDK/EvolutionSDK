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
	}
	
	public function _on_framework_loaded() {
		$enabled = e::$environment->requireVar('SQL.Enabled', "yes | no");
		
		if($enabled !== true && $enabled !== 'yes')
			return false;
			
		$this->sql = new SQLBundle($this->dir);
		
		if(is_object($this->sql))
			$this->sql->_sql_initialize();
	}
	
	public function _on_deferred_register() {
		$args = func_get_args();
		$name = array_shift($args);
		if(!is_string($name))
			throw new Exception("No string passed as first argument when running event `deferred_register`");
			
		e::$sql->insert('deferred.pending', array(
			'service' => $name,
			'key' => md5($name . microtime() . rand(0, 100000000)),
			'args' => serialize($args)
		));	
		
		e\trace("<b>Deferred Callback Registered</b>", '', null, 8);
	}
	
	public function _on_after_framework_loaded() {
		if(!is_object($this->sql))
			return;
		e\trace('Processing Deferred Events', '', null, 8);
		$pending = e::$sql->select('deferred.pending')->all();
		
		foreach($pending as $item) {
			
			// Simple access
			extract($item);
			
			// Get arguments
			$args = unserialize($args);
			if(!is_array($args))
				throw new Exception("Unserialize failed on deferred service `$service`");
			
			// Run the service
			call_user_func_array(array(e::$events, $service), $args);
			
			// If no errors, we can remove this from the table
			e::$sql->query("DELETE FROM `deferred.pending` WHERE `key` = '$key'");
		}
	}
}