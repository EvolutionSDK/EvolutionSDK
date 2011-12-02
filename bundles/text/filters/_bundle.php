<?php

namespace Bundles\Filters;
use Exception;
use e;

class Bundle {
	
	public function __invokeBundle($filter, $args) {
		return call_user_func_array('Filters::$filter', $args);
	}
	
}

class Filters {
	
	/**
	 * Force Calling of Filters to be done Statically
	 *
	 * @param string $function 
	 * @param string $args 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function __callStatic($function, $args) {
		if(method_exists($this, '_'.$function))
			return call_user_func_array(array($this, '_'.$function), $args);
			
		//else foreach();
	}
	
}