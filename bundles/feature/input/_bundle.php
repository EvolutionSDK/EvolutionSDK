<?php

namespace Bundles\Input;
use Exception;
use StdClass;
use e;

class Bundle extends \Bundles\SQL\SQLBundle {
	
	private $sources;
	
	public function __initBundle() {
		$sources = new StdClass;
		$sources->post 		= $_POST;
		$sources->get 		= $_GET;
		$sources->cookie	= $_COOKIE;
		$sources->files		= $this->files();
		$sources->all		= e\array_merge_recursive_simple($_REQUEST, array('files'=>$this->files()));
		$this->sources = $sources;
	}
	
	public function __callBundle($scan) {
		return new Scanner($this->sources, $scan);
	}
	
	private function files($lfiles = false, $top = true) {
		$files = array();
		foreach((!$lfiles ? $_FILES : $lfiles) as $name=>$file){
			if($top) $sub_name = $file['name'];
			else $sub_name = $name;
			
			if(is_array($sub_name)){
				foreach(array_keys($sub_name) as $key){
					$files[$name][$key] = array(
						'name'     => $file['name'][$key],
						'type'     => $file['type'][$key],
						'tmp_name' => $file['tmp_name'][$key],
						'error'    => $file['error'][$key],
						'size'     => $file['size'][$key],
						);
					$files[$name] = $this->files($files[$name], false);
				}
			}
			else $files[$name] = $file;
		}
    	return $files;
	}
	
	public function __get($source) {
		return $this->sources->$source;
	}
	
}

class Scanner {
	
	private $sources;
	private $scantype;
	private static $clean = array();
	private static $dirty = array();
	
	public function __construct(&$sources, $scan) {
		$this->sources =& $sources;
		$this->scantype = $scan;
	}
	
	public function __get($source) {
		if(isset(self::$dirty[$source]))
			throw new Exception("`$source` was already determined dirt when scanning with `".self::$dirty[$source]."`");
			
		if(!isset(self::$clean[$source])) {
			try { $return = call_user_func_array("$this->scantype::run", $this->sources->$source); }
			catch(Exception $e) {
				self::$dirty[$source] = $this->scantype;
				throw new Exception("There was an error scanning `$source` with `$this->scantype`");
			}
			self::$clean[$source] = $this->scantype;
			$this->sources->$source = $return;
		}
		
		return $this->sources->$source;
	}
}