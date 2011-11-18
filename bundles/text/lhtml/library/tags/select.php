<?php

namespace Bundles\LHTML;
use Exception;

class Node_Select extends Node {
	
	public function init() {
		$this->element = 'select';
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
		* If is a real element create the opening tag
		*/
		if($this->element !== '' && $this->element) $output .= "<$this->element".$this->_attributes_parse().'>';
		
		/**
		 * Render the code
		 */
		if(!empty($this->children)) foreach($this->children as $child) {
			if($child->fake_element !== 'option') continue;
							
			if(isset($this->attributes['selected']) && $this->_string_parse($this->attributes['selected']) == $child->attributes['value'])
				$child->attributes['selected'] = 'selected';
			
			if(is_object($child)) $output .= $child->build();
		}
		
		/**
		 * Close the tag
		 */
		if($this->element !== '' && $this->element) $output .= "</$this->element>";
		
		/**
		 * If a loop increment the pointer
		 */
		if($this->is_loop) $this->_data()->next();
		
		/**
		 * End build loop
		 */
		}
		
		if($this->is_loop) $this->_data()->reset();
		
		/**
		 * Return the rendered page
		 */
		return $output;
	}
	
}