<?php

namespace Bundles\PHP;
use Bundles\Manage\Tile;
use e;

/**
 * Evolution PHP Manage
 * @author Nate Ferrero
 */
class Manage {
	
	public $title = 'PHP';
	
	public function page($path) {
		ob_start();
		phpinfo();
		$buffer = ob_get_clean();
		return '<div class="section" style="margin-top: 1em">' . str_replace('font-family', 'x-font-family', $buffer) . '</div>';
	}
	
	public function tile() {
	    $tile = new Tile('php');
    	$tile->body .= '<h2>Information about the current PHP '.\PHP_VERSION.' installation.</h2>';
    	return $tile;
    }
}