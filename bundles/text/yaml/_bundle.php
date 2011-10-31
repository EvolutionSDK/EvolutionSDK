<?php

namespace bundles\yaml;
use Exception;

class Bundle extends DSpyc {
	
	public function load($string, $file = false) {
		if($file) {
			if(!is_file($string))
				throw new Exception("YAML: Trying to load missing file `$string`");
			return $this->file($string);
		}
		return $this->string($string);
	}
	
}