<?php

namespace Bundles\Language;
use Bundles\Manage\Tile;
use e;

/**
 * Evolution Language Manage
 */
class Manage {
	
	public $title = 'Language';
	
	public function page($path) {
		return 'lang';
	}
	
	public function tile() {
	    $tile = new Tile('environment');
    	$tile->body .= '<h2>Set up and manage site languages.</h2>';
    	return $tile;
    }
}