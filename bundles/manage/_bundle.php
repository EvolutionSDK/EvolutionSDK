<?php

namespace Bundles\Manage;
use Exception;
use stack;
use e;

class Bundle {
	
	public function _on_framework_loaded() {
		
		// Add manager
		e::configure('manage')->activeAddKey('bundle', __NAMESPACE__, 'manage');
	}

	public function route($path) {

		/**
		 * Security access for development
		 */
		e::$security->developerAccess();
		
		// Get name
		$name = array_shift($path);
		
        // Site sandbox TODO MOVE THIS SOMEWHERE ELSE?
        if($name == 'sandbox') {
        	$path = implode('/', $path);
        	if($path === '') $path = 'index';
        	$file = e\site . '/sandbox/' . $path . '.php';
        	$dir = dirname($file);
        	if(!is_dir($dir))
        		throw new Exception("Sandbox directory `$dir` does not exist");
        	chdir($dir);
        	echo '<style>body {margin: 0; padding: 0; font-family: Tahoma, Lucida Grande, Sans, sans-serif;}</style>';
        	echo '<div style="padding: 1em; background: black; box-shadow: 0 0 4px #000; color: #fff;"><b>Sandbox File </b>';
        	echo '<pre style="display: inline">'.$file.'</pre></div>';
        	require_once($file);
        	e\complete();
        }

		if($name === 'manage')
			e\redirect('/@manage');
		if($name === '' || is_null($name))
			$name = 'manage';
			
		$bundles = e::configure('manage')->bundle;
		
		$ns = array_search($name, $bundles);
		if($ns === false)
			throw new Exception("No manage panel for `$name`");
			
		$class = $ns . '\\Manage';
		$panel = new $class();
		
		$title = "EvolutionSDK&trade; &rarr; " . $panel->title;
		$css = file_get_contents(__DIR__.'/../debug/theme.css') . self::style();
		$header = '<span>EvolutionSDK</span> &rarr; <a href="/@manage">Manage System</a>' . ($name !== 'manage' ? " &rarr; <span>" . $panel->title . "</span>": '');
		echo "<!doctype html><html><head><title>$title</title><style>$css</style></head><body class='_e_dump'><h1>$header</h1><div class='manage-page'>";
		echo $panel->page($path);
		echo "</div></body></html>";
		e\complete();
	}
	
	public static function style() {
	$tileStyle = e\button_style('.tile');
	return <<<EOF
		$tileStyle
		.tile {
			float: left; width: 200px;
			font-size: 14px; height: 200px;
			padding: 15px;
			margin: 0 30px 30px 0;
		}
		.tile h1 {
			padding-bottom: 9px;
			margin: 0 0 12px;
			font-size: 28px;
			border-bottom: 1px solid #ccc;
		}
		.tile h2 {
			margin: 0 0 8px;
			font-size: 18px;
		}
		.tile > ul {
			padding: 0 0 0 18px;
		}
		.tile span.alert {
			color:red;
			font-size:10px;
			position:absolute;
			top:5px;
			right:3px;
		}
EOF;
	}
}