<?php

namespace Bundles\Security;
use Bundles\Manage\Tile;
use e;

/**
 * Evolution Security Manage
 * @author Nate Ferrero
 */
class Manage {
	
	public $title = 'Security';
	
	public function page($path) {
		
	}
	
	public function tile() {
	    $tile = new Tile('security');
    	$tile->body .= '<h2>Manage site security and authorization logs.</h2>';
    	return $tile;
    }
}