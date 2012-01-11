<?php

namespace Bundles\Unit;
use Bundles\Manage\Tile;
use stack;
use e;

/**
 * Evolution Unit Test Manage Class
 * @author Nate Ferrero
 */
class Manage {
	
	public $title = 'Unit Tests';
	
	/**
	 * Categorize and display available tests
	 * @author Nate Ferrero
	 */
	public function page($path) {
		
		$all = array();
		
		echo '<style>' . file_get_contents(__DIR__ . '/unit-style.css') . '</style>';
		echo '<script type="text/javascript">' . file_get_contents(__DIR__ . '/jquery-1.7.min.js') . '</script>';
		echo '<script type="text/javascript">' . file_get_contents(__DIR__ . '/unit-script.js') . '</script>';
		echo '<div class="controls">Controls: <a onclick="unit.start()">Start</a>|<a onclick="unit.pause()">Pause</a>|<a onclick="unit.reset()">Reset</a></div>';
		
		foreach(stack::__bundle_locations() as $bundle => $location) {
			$location = explode(DIRECTORY_SEPARATOR, $location);
			$category = array_pop($location);
			if(!isset($all[$category]))
				$all[$category] = array();
			
			/**
			 * Get unit tests for bundle
			 */
			$all[$category][$bundle] = e::$unit->getTests($bundle);
		}
		
		foreach($all as $category => $bundles)
			$max = max($max, e\deep_count($bundles));
		
		echo '<ul class="categories">';
		foreach($all as $category => $bundles) {
			$total = e\deep_count($bundles);
			$width = 140;
			$spread = 280;
			$width += $spread * $total / $max;
			echo "<li class='category'><h1>$category <span class='tests bar-off' style='width: ${width}px'><span class='bar-green'></span><span class='text'><span class='passed'>0</span> of <span class='total'>$total</span> tests passed</span></span></h1>";
			echo '<ul class="bundles">';
			foreach($bundles as $bundle => $tests) {
				echo "<li class='bundle'><div class='name'>$bundle</div><ul class='tests'>";
				foreach($tests as $test) {
					echo '<li class="test"><div class="led led-off"></div><span class="description">'.($test->description ? $test->description : '<em>No description</em>').'</span></li>';
				}
				echo '</ul></li>';
			}
			echo '</ul>';
		}
		echo '</ul>';
	}
	
	/**
	 * Return the manage tile for unit testing
	 * @author Nate Ferrero
	 */
	public function tile() {
	    $tile = new Tile('unit');
    	$tile->body .= '<h2>Verify the integrity of your software with unit tests.</h2>';
    	return $tile;
    }
}