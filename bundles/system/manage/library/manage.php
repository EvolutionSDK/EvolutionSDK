<?php

namespace Bundles\Manage;
use Exception;
use e;

/**
 * Evolution Manage Manage
 * Shows all management pages
 */
class Manage {
	
	public $title = 'Manage System';
	
	private static $bundles = array();
	
	public function page() {
		self::$bundles = e::configure('manage')->bundle;
		$out = '<div style="padding: 30px;">';
		foreach(self::$bundles as $namespace => $link) {
			try {
				if(strlen($namespace) < 3)
					throw new Exception("Invalid namespace `$namespace` for manage page `$link`");
				$class = "$namespace\\Manage";
				$manage = new $class;
				$tile = $manage->tile();
				if(is_null($tile))
					continue;
				if(!($tile instanceof Tile))
					throw new Exception("Bundle manager `$class` tile is not a valid Tile class");
				$out .= $tile->output($manage->title);
			} catch(Exception $e) {
				e\trace_exception($e);
				$tile = $this->tile($e->getMessage());
				$out .= $tile->output($link);
			}
		}
		$out .= '</div>';
		return $out;
	}
	
	public function tile($body = false) {
		if(!$body && count(self::$bundles) > 0)
			return null;
		$tile = new Tile('', 'alert');
		if($body)
			$tile->body = $body;
		else
			$tile->body = 'No installed bundles have manage pages, install a bundle to show tiles here.';
		return $tile;
	}
	
}

/**
 * A tile
 */
class Tile {
	
	public $title;
	public $body = '';
	public $class = '';
	
	public function __construct($link = '', $class = '') {
		
		$this->link = $link;
		$this->class = $class;
			
	}
	
	public function output($title) {
		if(strlen($this->link) > 0)
			$this->link = '/' . $this->link;
		return '<a class="tile '.$this->class.'" href="/@manage'.$this->link.'">'.
			'<h1>'. (strpos($this->class, 'alert') !== false ? '&#9733; ' : '') . $title.'</h1>'.
			$this->body.'</a>';	
	}
}