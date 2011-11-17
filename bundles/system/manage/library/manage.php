<?php

namespace Bundles\Manage;

/**
 * Evolution Manage Manage
 * Shows all management pages
 */
class Manage {
	
	public $title = 'No Bundles Found';
	
	private static $bundles = array();
	
	public function page() {
		self::$bundles = e::configure('manage')->bundle);
		$out = '<div style="padding: 30px;">';
		foreach(self::$bundles as $namespace => $link) {
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
		}
		$out .= '</div>';
		return $out;
	}
	
	public function tile() {
		if(count(self::$bundles) > 0)
			return null;
		$tile = new Tile('', 'alert');
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
		return '<a class="tile '.$this->class.'" href="@manage/'.$this->link.'">'.
			'<h1>'. (strpos($this->class, 'alert') !== false ? '&#9733; ' : '') . $title.'</h1>'.
			$this->body.'</a>';	
	}
}