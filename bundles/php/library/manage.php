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

	public function test() {
		$devmode = e::$environment->requireVar('Development.Master', 'yes | no');
		if(!($devmode === true || $devmode === 'yes')) return 'Access Denied.';

		$post = e::$resource->noscan->post;
		ob_start();
		
		if(isset($post['test'])) {
			echo '<div class="section" style="margin-top: 1em"><pre>';
			eval($post['test']);
			echo '</pre></div>';
		}

		echo '<div class="section" style="margin-top: 1em">';
		echo '<form method="POST"><textarea name="test" style="width:100%;height:300px;"></textarea><input type="submit" /></form>';
		echo '</div>';

		return ob_get_clean();
	}
	
	public function page($path) {
		$devmode = e::$environment->requireVar('Development.Master', 'yes | no');
		if(array_shift($path) === 'test' && ($devmode === true || $devmode === 'yes'))
			return $this->test();

		ob_start();
		phpinfo();
		$buffer = ob_get_clean();

		if($devmode === true || $devmode === 'yes')
			$test = '<div class="section" style="margin-top: 1em"><a href="/@manage/php/test">Test E3 Code</a></div>';
		else $test = '';
		return $test.'<div class="section" style="margin-top: 1em">' . str_replace('font-family', 'x-font-family', $buffer) . '</div>';
	}
	
	public function tile() {
	    $tile = new Tile('php');
    	$tile->body .= '<h2>Information about the current PHP '.\PHP_VERSION.' installation.</h2>';
    	return $tile;
    }
}