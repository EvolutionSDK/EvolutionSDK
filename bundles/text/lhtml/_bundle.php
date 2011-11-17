<?php

namespace bundles\LHTML;
use Exception;
use e;

class Bundle {
	
	public function __bundle_response() {
		return new Instance;
	}
	
	public function _on_first_use() {
		Scope::addHook(':e', new e_handle);
	}
	
	public function _on_lhtml_add_hook($hook, $item) {
		
	}
	
	public function _on_portal_route($path, $dir) {
		$this->route($path, array($dir));
	}
	
	public function _on_router_route($path) {
		$this->route($path, array(e::$site));
	}
	
	public function route($path, $dirs = null) {
		
		// If dirs are not specified, use defaults
		if(is_null($dirs))
			$dirs = e::configure('lhtml')->locations;
		
		// Make sure path contains valid controller name
		if(!isset($path[0]) || $path[0] == '')
			$path = array('index');
		
		// Get the lhtml name
		$name = strtolower(implode('/', $path));
		
		e\Trace(__CLASS__, "Looking for $name.lhtml");
		
		// Check all dirs for a matching lhtml
		foreach($dirs as $dir) {
			// Look in lhtml folder
			if(basename($dir) !== 'lhtml')
				$dir .= '/lhtml';
			
			// Skip if missing
			if(!is_dir($dir))
				continue;
				
			// File to check
			$file = "$dir/$name.lhtml";
			
			// Skip if incorrect file
			if(!is_file($file)) {
				$file = "$dir/$name/index.lhtml";
				if(!is_file($file)) continue;
			}
	
			// Parse the lhtml file and build the stack
			echo e::lhtml()->file($file)->parse()->build();
			            
            // Complete the current binding queue
            e\complete();
		}
	}
}

class Instance {
	
	private $file;
	private $stack;
	
	public function file($file) {
		$this->file = $file;
		if($this->stack)
			unset($this->stack);
		return $this;
	}
	
	public function parse() {
		if(!isset($this->file))
			throw new Exception("LHTML: No file specified to parse");
		$this->stack = Parser::parseFile($this->file);
		return $this;
	}
	
	public function build() {
		if(!isset($this->stack))
			$this->parse();
		return $this->stack->build();
	}

}

class e_handle {
	
	public function __call($method, $args) {
		$method = "e::$method";
		return call_user_func_array($method, $args);
	}
	
}