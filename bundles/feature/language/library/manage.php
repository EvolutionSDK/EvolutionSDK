<?php

namespace Bundles\Language;
use Bundles\Manage\Tile;
use e;

/**
 * Evolution Language Manage
 * @author Nate Ferrero
 */
class Manage {
	
	public $title = 'Language';
	
	public function page($path) {
		return 'lang';
	}
	
	public function tile() {
	    $tile = new Tile('language');
    	$tile->body .= '<h2>Set up and manage site languages.</h2>';
    	return $tile;
    }
}