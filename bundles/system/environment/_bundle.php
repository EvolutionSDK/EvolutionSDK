<?php

namespace bundles\environment;
use Exception;
use bundles\SQL\SQLBundle;
use e;

/**
 * Environment Bundle
 */
class Bundle {
	
	private static $environment = null;
	private static $scope = 'global';
	private static $file;
	private static $url;
	public $active = false;
	
	public function __construct($dir) {
		/**
		 * Load from EvolutionSDK root folder
		 */
		self::load(e\root.'/cache');
		self::addScope('site.'.basename(e::$site));
		
		/**
		 * Add to manager
		 */
		e\configure::add('manage.bundle', __CLASS__);
	}
	
	/**
	 * Non-static Versions
	 */
	public function invalidVar($var, $ex = null, $addscope) {
		return self::_invalid($var, $ex);
	}
	
	public function requireVar($var, $format = '/.+/', $why = 'Not Set', $new = false, $ex = null) {
		$this->active = true;
		return self::_require($var, $format, $why, $new, $ex);
	}
	
	/**
	 * Static Versions
	 */
	public static function _invalid($var, $ex = null) {
		return self::_require($var, self::$environment[self::$scope . ".$var---format"], 'is Invalid', true, $ex);
	}
	
	public static function _require($var, $format = '/.+/', $why = 'Not Set', $new = false, $ex = null, $addscope = true) {
		
		// Add current scope
		if($addscope)
			$var = self::$scope . ".$var";
		
		// Save the format if not present
		if(!isset(self::$environment[$var."---format"])
			|| self::$environment[$var."---format"] != $format) {
			self::$environment[$var."---format"] = $format;
			self::save();
		}
		
		// Check for var
		$value = '';
		if(isset(self::$environment[$var])) {
			$value = self::$environment[$var];
			if(!$new)
				return $value;
		}
		
		// Get exception display
		if($ex instanceof Exception)
			$ex = e\render_exception($ex);
		else
			$ex = '';
			
		// If not found show the form
		$title = "EvolutionSDK&trade; Edit Environment Variable";
		$header = '<span>EvolutionSDK</span> &rarr; <a href="/@manage">Manage System</a> &rarr; <a href="/@manage/environment">Environment</a> &rarr; <span>Edit Variable</span>';
		$css = file_get_contents(e\root.e\bundles.'/system/debug/theme.css');
		echo "<!doctype html><html><head><title>$title</title><style>$css</style></head><body><h1>$header</h1>";
		echo self::requireForm($var, $value, $format, $why).$ex;
		die;
		echo "</body></html>";
		e\complete();
	}
	
	public function update() {
		$return = '/';
		foreach($_POST as $var => $value) {
			$var = base64_decode($var);
			if($var == '_return')
				$return = $value;
			else if(strlen($var) > 1)
				self::$environment[$var] = $value;
		}
		self::save();
		e\redirect($return);
	}
	
	public function debug() {
		$env = self::$environment;
		eval(d);
	}
	
	public static function getAll() {
		return self::$environment;
	}
	
	private static function requireForm($var, $value, $format, $why) {
		
		if(is_null(self::$url))
			self::$url = e::router()->url;
		
		$out = '<div class="section"><h1>Required Variable <code>'.htmlspecialchars($var).'</code> '.$why.'</h1>';
		$out.= '<p>Environment variables are saved separately on each server and are particular to the current system configuration.</p>';
		$out.= '<form action="/@environment.update" method="post"><h4>Set Variable</h4><div class="trace">';
		$out.= '<p>Required Format: <code>'.htmlspecialchars($format).'</code></p>';
		$out.= '<input type="hidden" name="'.base64_encode('_return').'" value="'.htmlspecialchars(self::$url).'"/>';
		$out.= '<div class="pad-sides">';
		
		if(preg_match('/([\w\s]+\|[\w\s]+)+/', $format)) {
			$out .= '<select name="'.base64_encode($var).'">';
			foreach(explode('|', $format) as $sval) {
				$sval = trim($sval);
				$selected = $value == $sval ? ' selected="selected"' : '';
				$out .= '<option value="'.$sval.'"'.$selected.'>'.$sval.'</option>';
			}
			$out .= '</select>';
		} else {
			$out .= '<textarea name="'.base64_encode($var).'">'.htmlspecialchars($value).'</textarea>';
		}
		
		$out.= '</div>';
		$out.= '<input type="submit" value="Save Environment Variable" /></div></form>';
		
		return $out;
	}
	
	public static function addScope($scope) {
		self::$scope .= ".$scope";
	}
	
	public static function save() {
		
		// Save environment configuration
		e\encode64_file(self::$file, self::$environment);
	}
	
	public static function load($dir) {
		
		// Set environment file path
		self::$file = "$dir/.environment";
		
		// Create file if not exists
		if(!is_file(self::$file))
			e\encode64_file(self::$file, array());
			
		// Load environment file
		self::$environment = e\decode64_file(self::$file, true);
	}
	
	public function route($path) {
		$func = 'do'.ucfirst(array_shift($path));
		$this->$func(base64_decode(array_shift($path)));
		self::save();
		e\redirect('/@manage/environment');
	}
	
	public function doDelete($key) {
		unset(self::$environment[$key]);
		unset(self::$environment[$key.'---format']);
	}
	
	public function doEdit($key) {
		self::$url = '/@manage/environment';
		self::_require($key, self::$environment[$key.'---format'], 'can be edited!', true, null, false);
	}
}