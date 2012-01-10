<?php

namespace Bundles\LHTML;
use Exception;
use stack;
use e;

/**
 * Template Class
 *
 * @package default
 * @author Kelly Lauren Summer Becker
 */
class Node_Template extends Node {
	
	public function init() {
		$this->element = false;
	}
	
	public function build() {
		$found = false;
		$hook = $this->attributes;
		
		foreach($this->children as $child) {
			if(!is_object($child))
				continue;			
			if(!($child instanceof Node && strpos($child->fake_element, 'template:') === 0))
				continue;
				
			$name = str_replace('template:', '', $child->fake_element);
			$child->element = false;
			
			$hook = e\array_merge_recursive_simple($hook, array(
				$name => $child->build()
			));
			
			$found = true;
		}
		
		if(!$found) {
			$hook = e\array_merge_recursive_simple($hook, array(
				'content' => parent::build()
			));
		}
		
		foreach($hook as $key => $val) {
			$keys[] = "%$key%";
			$vals[] = $val;
		}
		
		$file = empty($this->attributes[':file']) ? 'default' : $this->attributes[':file'];
		
		$string = str_replace($keys, $vals, 
			file_get_contents(e::$portal->currentPortalDir().'/lhtml/--assets/templates/'.$file.'.lhtml'));
		
		return e::$lhtml->string($string)->build();
	}
	
}

/**
 * Mini Template Class
 *
 * @package default
 * @author Kelly Lauren Summer Becker
 */
class Node_T extends Node {
	
	private static $templates = array();
	
	public function init() {
		if(empty(self::$templates))
			self::$templates = e::$yaml->file(e::$portal->currentPortalDir().'/lhtml/--assets/mini-t.yaml');
		
		$this->fake_element = substr($this->fake_element, 3);
		$this->element = false;
	}
	
	public function build() {
		$found = false;
		$attr = $this->attributes;
		
		if(!isset(self::$templates[$this->fake_element]))
			throw new Exception("Could not find the pattern for `$this->fake_element`");
		
		$grab = self::$templates[$this->fake_element];
		$keys = array(); $vals = array(); $build = '';
		
		$attr['value'] = parent::build();
		
		foreach($attr as $key => $val) {
			$keys[] = "%$key%";
			$vals[] = $val;
		}
		
		return e::$lhtml->string(str_replace($keys, $vals, $grab))->build();
	}
	
}