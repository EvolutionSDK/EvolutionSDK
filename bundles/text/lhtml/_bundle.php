<?php

namespace bundles\LHTML;
use e\Configure;
use Exception;

class Bundle {
	
	public function __bundle_response() {
		return new Instance;
	}
	
	public function _on_lhtml_add_hook($hook, $item) {
		
	}
	
	public function _on_router_route($path, $dirs = null) {
		
		// If dirs are not specified, use defaults
		if(is_null($dirs))
			$dirs = Configure::getArray('lhtml.location');
		
		// Make sure path contains valid controller name
		if(!isset($path[0]) || $path[0] == '')
			return;
		
		// Get the lhtml name
		$name = strtolower($path[0]);
		
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
			if(!is_file($file))
				continue;
	
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
	
	public function __construct($dir) {
		
		/**
		 * Add lhtml to default router and portal routing
		 */
		Configure::add('portal.defaults.run_with', 'lhtml');
		Configure::add('router.defaults.run_with', 'lhtml');
	}
	
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