<?php

namespace Bundles\LHTML;
use Exception;
use e;

class Node_Template extends Node {
	
	public function init() {
		$this->element = false;
	}
	
	public function build() {
		$hook = $this->attributes;
		$hook = e\array_merge_recursive_simple($hook, array(
			'children' => parent::build()
		));
		
		$data = $this->_data();
		$dir = realpath(dirname($data->__file__));
		$v = $this->attributes['src'];	
		
		$vars = $this->extract_vars($v);
		if($vars) foreach($vars as $var) {
			$data_response = $data->$var;	
			$v = str_replace('{'.$var.'}', $data_response, $v);				
		}
		
		$v = "$dir/$v";
		
		if(pathinfo($v, PATHINFO_EXTENSION) !== 'lhtml')
			$v .= '.lhtml';
		
		$tmp = Scope::getHook(':vars');
		Scope::addHook(':vars', $hook);
		$return = e::$lhtml->file($v)->build();
		if(!$tmp) Scope::rmvHook(':vars');
		else Scope::addHook(':vars', $tmp);
		return $return;
	}
	
}