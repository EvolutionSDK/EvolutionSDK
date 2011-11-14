<?php

namespace bundles\Environment;
use bundles\Manage\Tile;
use e;

/**
 * Evolution Environment Manage
 */
class Manage {
	
	public $title = 'Environment';
	
	public function page($path) {
		$out = '';
		$env = Bundle::getAll();
		$categories = array();
		
		foreach($env as $key => $value) {
			if(substr($key, -9) === '---format')
				continue;
			$tmp = explode('.', $key);
			if(isset($tmp[3])) {
				$category = "$tmp[0].$tmp[1].$tmp[2]";
				array_shift($tmp);
				array_shift($tmp);
				array_shift($tmp);
				$name = implode('.', $tmp);
			} else {
				$category = 'Misc';
				$name = $key;
			}
			if(!isset($categories[$category]))
				$categories[$category] = array();
			
			$delete = '/@environment/delete/' . base64_encode($key);
			$delete = '<a href="'.$delete.'">Delete</a>';
			$edit = '/@environment/edit/' . base64_encode($key);
			$edit = '<a href="'.$edit.'">Edit</a>';
			
			/**
			 * Save the HTML
			 */
			$categories[$category][$name] = "<div class='var'><h3>$name</h3>$edit &bull; $delete &bull; <code>$value</code></div>";
		}
		
		foreach($categories as $category => $items) {
			$out .= '<div class="section"><h1>'.$category.'</h1>';
			$out .= implode('', $items);
			$out .= '</div>';
		}
		return $out;
	}
	
	public function tile() {
	    $tile = new Tile('environment');
    	$tile->body .= '<h2>Configure your environment settings.</h2>';
    	return $tile;
    }
}