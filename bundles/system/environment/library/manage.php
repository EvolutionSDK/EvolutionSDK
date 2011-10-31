<?php

namespace Evolution\Environment;
use Evolution\Router;
use Evolution\Manage\Tile;
use e;

/**
 * Evolution Environment Manage
 */
class Manage {
	
	public $title = 'Environment';
	
	public function page($path) {
		$out = '<div class="section"><h1>Global Variables</h1>';
		$env = Bundle::getAll();
		foreach($env as $key => $value) {
			if(substr($key, -9) === '---format')
				continue;
			$delete = Router\BundleURL('environment') . '/delete/' . base64_encode($key);
			$delete = '<a href="'.$delete.'">Delete</a>';
			$edit = Router\BundleURL('environment') . '/edit/' . base64_encode($key);
			$edit = '<a href="'.$edit.'">Edit</a>';
			$out .= "<div class='var'>$edit &bull; $delete &bull; <code class='alt'>$key</code> = <code>$value</code></div>";
		}
		$out .= '</div>';
		return $out;
	}
	
	public function tile() {
	    $tile = new Tile('environment');
    	$tile->body .= '<h2>Configure your environment settings.</h2>';
    	return $tile;
    }
}