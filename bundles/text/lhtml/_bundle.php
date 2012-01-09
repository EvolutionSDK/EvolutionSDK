<?php

namespace bundles\LHTML;
use Exception;
use stack;
use e;

class Bundle {
	public static $url_vars = array();
	
	public function __getBundle() {
		return new Instance;
	}
	
	public function __initBundle() {
		Scope::addHook(':e', new e_handle);
		Scope::addHook(':slug', function() { return e::$lhtml->_get_special_vars(':slug'); });
		Scope::addHook(':id', function() { return e::$lhtml->_get_special_vars(':id'); });
		Scope::addHook(':urlVars', function() { return e::$lhtml->_get_special_vars(':urlVars'); } );
	}
	
	public function _on_lhtml_add_hook($hook, $item) {
		
	}
	
	public function _on_portal_route($path, $dir) {
		$this->route($path, array($dir));
	}
	
	public function _on_router_route($path) {
		$this->route($path, array(stack::$site));
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
			
			$matched = false;	$vars = array();	$nodir = false; $badmatch = false;
			$p = 1;
			foreach($path as $key => $segment) {
				if($matched == 'file') $vars[] = $segment;
				if((!$matched || $matched == 'dir') && is_dir("$dir/$segment")) {
					$dir .= "/$segment";
					$matched = 'dir';
				}
				elseif(is_file("$dir/$segment.lhtml")) {
					$file = "$dir/$segment.lhtml";
					$matched = 'file';
				}
				elseif($matched != 'file') {
					$badmatch = true;
				}
			}
			
			if(!$badmatch && $matched != 'file' && is_file("$dir/index.lhtml")) {
				$file = "$dir/index.lhtml";
				$matched = 'index';
			}

			# no match at all, just continue
			if($matched == false) continue;
			
			# set the url vars to use
			self::$url_vars = $vars;
			
			// Parse the lhtml file and build the stack
			echo e::$lhtml->file($file)->parse()->build();
			            
            // Complete the current binding queue
            e\Complete();
		}
	}
}

class Instance {
	
	private $file;
	private $string;
	private $stack;
	
	public function _get_special_vars($matcher) {
		switch($matcher) {
			case ':id' :
				if(isset(Bundle::$url_vars[0]) && is_numeric(Bundle::$url_vars[0])) return Bundle::$url_vars[0];
			break;
			case ':slug':
				if(isset(Bundle::$url_vars[0])) return Bundle::$url_vars[0];
			break;
			case ':urlVars':
				if(isset(Bundle::$url_vars[0])) return Bundle::$url_vars;
			break;
		}
		return null;
	}
	
	public function file($file) {
		$this->file = $file;
		if($this->stack)
			unset($this->stack);
		return $this;
	}
	
	public function string($string) {
		$this->string = $string;
		if($this->stack)
			unset($this->stack);
		return $this;
	}
	
	public function parse() {
		if(!isset($this->file) && !isset($this->string))
			throw new Exception("LHTML: No file or string specified to parse");
		
		if(isset($this->file)) {
			$this->stack = Parser::parseFile($this->file);
			unset($this->file);
		}
		else if(isset($this->string)) {
			$this->stack = Parser::parseString($this->string);
			unset($this->string);
		}
		
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
		if(!empty($args)) {
			$method = "e::$method";
			return call_user_func_array($method, $args);
		}
		
		return e::$$method;
	}
	
}