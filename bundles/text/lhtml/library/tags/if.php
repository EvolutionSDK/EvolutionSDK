<?php

namespace Bundles\LHTML;
use Exception;

class tag_if extends Node {
	
	public function init() {
		$this->element = false;
	}
	
	public function build() {		
		$this->_init_scope();
		$output = "";
		
		/**
		 * If requires iteration
		 * else just execute the loop once
		 */
		if($this->is_loop) {
			$this->_data()->reset();
		} else {
			$once = 1;
		}
		
		/**
		 * Start build loop
		 */
		while($this->is_loop ? $this->_data()->iteratable() : $once--) {
		
		/**
		 * Render the code
		 */
		if($this->process()) {
			if(!empty($this->children)) foreach($this->children as $child) {			
				if(is_object($child)) $output .= $child->build();
				else if(is_string($child)) $output .= $this->_string_parse($child);
			}
		}
		else {
			if(!empty($this->children)) foreach($this->children as $child) {	
				if(is_object($child)) {
					if($child->fake_element == ':else') $output .= $child->built();
				}
			}
		}
		
		/**
		 * If a loop increment the pointer
		 */
		if($this->is_loop) $this->_data()->next();
		
		/**
		 * End build loop
		 */
		}
		
		/**
		 * Return the rendered page
		 */
		return $output;
	}
	
	public function process() {
		if(!isset($this->attributes['cond']))
			throw new Exception('No if-condition `cond` specified');
		
		$v = $this->attributes['cond'];
		
		$vars = $this->extract_vars($v);
		if($vars) foreach($vars as $var) {
			$data_response = $this->_data()->$var;
			$v = str_replace('{'.$var.'}', $data_response, $v);				
		}
		
		$v = str_replace('\'', '', $v);
		
		$v = explode(' ', $v);

		if($v[0] === '') $v[0] = null;
		if($v[2] === '') $v[2] = null;
		if($v[0] === 'null') $v[0] = null;
		if($v[2] === 'null') $v[2] = null;
		if($v[0] === 'true') $v[0] = true;
		if($v[2] === 'true') $v[2] = true;
		if($v[0] === 'false') $v[0] = false;
		if($v[2] === 'false') $v[2] = false;
		
		if(is_string($v[0])) $v[0] = "'".$v[0]."'";
		if(is_string($v[2])) $v[2] = "'".$v[2]."'";
		
		if($v[0] === null) $v[0] = 'null';
		if($v[2] === null) $v[2] = 'null';
		if($v[0] === true) $v[0] = 'true';
		if($v[2] === true) $v[2] = 'true';
		if($v[0] === false) $v[0] = 'false';
		if($v[2] === false) $v[2] = 'false';
		
		$v = array($v[0], $v[1], $v[2]);
		
		$v = implode(' ', $v);
				
		eval("\$retval = ".$v.';');
		
		if(!$retval) foreach($this->children as $child) {
			if(isset($child->fake_element) && $child->fake_element == ':else') $child->show_else = 1;
		}
		
		return $retval;
	}
	
}


class tag_else extends Node {
	public $show_else = 0;
	public function __construct($element = false, $parent = false) {
		parent::__construct($element, $parent);
		$this->element = false;
		
		if($this->show_else == 1) {
			$this->show_else = 0;
		}
	}
	
	public function build() {
		$this->_init_scope();
		$output = "";
		
		/**
		 * If requires iteration
		 * else just execute the loop once
		 */
		if($this->is_loop) {
			$this->_data()->reset();
		} else {
			$once = 1;
		}
		
		/**
		 * Start build loop
		 */
		while($this->is_loop ? $this->_data()->iteratable() : $once--) {
		
		/**
		 * Render the code
		 */
		if($this->show_else) {
			if(!empty($this->children)) foreach($this->children as $child) {			
				if(is_object($child)) $output .= $child->build();
				else if(is_string($child)) $output .= $this->_string_parse($child);
			}
		}
		
		/**
		 * If a loop increment the pointer
		 */
		if($this->is_loop) $this->_data()->next();
		
		/**
		 * End build loop
		 */
		}
		
		/**
		 * Return the rendered page
		 */
		return $output;
	}
	
}