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
		
		$hook = e\array_merge_recursive_simple($hook, array(
			'content' => parent::build()
		));
		
		$file = empty($this->attributes[':file']) ? 'default' : $this->attributes[':file'];
		
		if(trim($file) == 'default' && strlen(trim($hook['sidebar'])) > 2)
			$file = 'default-sidebar';
		
		Scope::addHook(':page', $hook);
		return e::$lhtml->file(stack::$site.'/configure/templates/'.$file.'.lhtml')->build();
	}
	
}

class Node_T extends Node {
	
	private static $templates = array();
	
	public function init() {
		if(empty(self::$templates))
			self::$templates = e::$yaml->file(e::$portal->currentPortalDir().'/lhtml/mini-t.yaml');
		
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
		
		return e::$lhtml->string(str_replace($keys, $vals, $grab))->parse()->build();
	}
	
}