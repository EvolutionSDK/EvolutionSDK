<?php

namespace Evolution\LHTML;
use Exception;

class tag_include extends Node {
	
	public function init() {
		$this->element = false;
	}
	
	public function build() {
		$this->process();
		return parent::build();
	}
	
	public function process() {
		$data = $this->_data();
		$dir = realpath(dirname($data->__file__));
		$v = $this->attributes['file'];	
		
		$vars = $this->extract_vars($v);
		if($vars) foreach($vars as $var) {
			$data_response = $data->$var;	
			$v = str_replace('{'.$var.'}', $data_response, $v);				
		}
		
		$v = "$dir/$v";
		
		if(pathinfo($v, PATHINFO_EXTENSION) !== 'lhtml')
			$v .= '.lhtml';

		// Set the children to an array with one element
		$this->children = array(Parser::parseFile($v));
	}
	
}