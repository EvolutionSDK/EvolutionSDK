<?php

namespace bundles\router;
use Exception;
use e;

class Bundle {
	
	public $url;
	public $path;
	
	public function _on_framework_loaded() {
		
		$url = $_SERVER['REDIRECT_URL'];
		$path = explode('/', $url);
		if($path[0] === '')
		    array_shift($path);
		
		/**
		 * Direct bundle access
		 */
		if(substr($url, 0, 2) == '/@') {
			$tmp = array_shift($path);
			$tmp = explode('.', substr($tmp, 1));
			if(count($tmp) < 2)
				$tmp[] = 'route';
			list($class, $method) = $tmp;
			e\trace(__CLASS__, "URL access for method `$method` on bundle `$class`");
			if(strlen($class) === 0)
				throw new Exception("No bundle specified for routing after `@`");
			$bundle = \e::$class();
			if(!method_exists($bundle, $method))
				throw new Exception("No method `$method` found for bundle `$class`");
		
			e\trace(__CLASS__, "Processing `".get_class($bundle)."->$method()`");
			$bundle->$method($this->path);
			e\complete();
		}
	}
	
	public function _on_route($url) {
		
		$this->url = $url;
		
		$this->path = explode('/', $this->url);
		if($this->path[0] === '')
		    array_shift($this->path);
		if($this->path[count($this->path) - 1] === '')
		    array_pop($this->path);
		
		e::events()->router_route($this->path);
	}
	
}