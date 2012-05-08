<?php

namespace bundles\yaml;
use Exception;
use e;

class Bundle extends DSpyc {
	
	public function load($string, $file = false) {
		if($file) {
			if(!is_file($string))
				throw new Exception("YAML: Trying to load missing file `$string`");
			return $this->file($string);
		}
		return $this->string($string);
	}

	public function merge() {
		$args = func_get_args();

		if(is_bool(end($args)))
			$file = array_pop($args);

		if(is_array($args[0]))
			$data = $args[0];
		else $data = $args;

		$merge = array();
		foreach($data as $load) {
			if($file) {
				if(!is_file($load))
					throw new Exception("YAML: Trying to load missing file `$string`");
				$merge[] = $this->file($load);
			}
			else $merge[] = $this->string($load);
		}

		return e\subarrays_merge_recursive_simple($merge);
	}
	
	public function model($file) {
		return new Yaml_Result($file);
	}
	
}