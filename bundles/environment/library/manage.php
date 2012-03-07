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

		$out .= '<script>function newVar() {
					window.location.replace(
						"/@environment/edit?var="+
						encodeURIComponent(prompt("Enter the key for the new variable:"))
					);
				}</script>';
		$out .= '<button style="position: fixed; top: 2.75em; right: 3em;" onclick="newVar();">New Environment Variable</button>';

		$env = Bundle::getAll();
		$categories = array();
		
		foreach($env as $key => $value) {
			if(substr($key, -9) === '---format')
				continue;
			$tmp = explode('.', $key);
			if(isset($tmp[1])) {
				$category = $tmp[0];
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
			if($value === true)
				$value = 'true';
			else if($value === false)
				$value = 'false';
			$categories[$category][$name] = "<div class='var'><h3>$name <span style='font-size: 80%; font-weight: normal'>$edit &bull; $delete</span></h3><code>$value</code></div>";
		}
		
		foreach($categories as $category => $items) {
			$out .= '<div class="section" style="clear:both; padding-top: 1px; margin-top: -1px;"><h1>'.$category.'</h1>';
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