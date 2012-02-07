<?php

namespace Bundles\Environment;
use Exception;
use Bundles\SQL\SQLBundle;
use stack;
use e;

/**
 * Environment Bundle
 */
class Bundle {
	
	private static $in_exception = false;
	private static $environment = array();
	private static $scope = '';
	private static $file;
	private static $url;
	public $active = false;
	
	public function __construct($dir) {
		/**
		 * Load from EvolutionSDK sites folder
		 */
		self::$file = e\root . '/cache/' . basename(e\site) . '/environment.yaml';
	}
	
	public function __initBundle() {
		self::load();
	}
	
	/**
	 * Require dev mode before page load
	 */
	public function _on_framework_loaded() {
		
		/**
		 * Add to manager
		 */
		e::configure('manage')->activeAddKey('bundle', __NAMESPACE__, 'environment');
		
		/**
		 * Check dev mode to avoid issues later
		 */
		e::$environment->requireVar('Development.Master', 'yes | no');
	}
	
	/**
	 * Non-static Versions
	 */
	public function invalidVar($var, $ex = null) {
		return $this->_invalid($var, $ex);
	}
	
	public function requireVar($var, $format = '/.+/', $why = 'Not Set', $new = false, $ex = null) {
		return $this->_require($var, $format, $why, $new, $ex);
	}
	
	public function getVar($var, $format = '/.+/', $why = 'Not Set', $new = false, $ex = null) {
		return $this->_require($var, $format, $why, $new, $ex, false);
	}
	
	public function _reset_exception_status() {
		self::$in_exception = false;
	}
	
	/**
	 * Static Versions
	 */
	public function _invalid($var, $ex = null) {
		return $this->_require($var, self::$environment[self::$scope . ".$var---format"], 'is Invalid', true, $ex);
	}
	
	public function _require($var, $format = '/.+/', $why = 'Not Set', $new = false, $ex = null, $throw = true) {
		
		if($_SERVER['REQUEST_URI'] == '/@environment/update') return;
		
		// Make active to prevent exceptions
		$this->active = true;
		
		// If in exception, just return null
		if(self::$in_exception)
			return null;
		
		// Check for var
		$orig = $var;
		$var = strtolower($var);
		$value = '';
		if(isset(self::$environment[$var])) {
			$value = self::$environment[$var];
			if(!$new)
				return $value;
		}

		/**
		 * Is using getVar return null on no variable.
		 * @author: Kelly Lauren Summer Becker
		 */
		if($throw == false) return null;

		/**
		 * Security access for development
		 */
		e::$security->developerAccess();
		
		$file = self::$file;
		
		// Testing form again
		// self::$in_exception = true;
		// if($throw) throw new Exception("<strong>Environment Variable $why:</strong> `$orig` in `$file` &rarr; <strong>Required Format:</strong> `$format`", 0, $ex);
		
		// Get exception display
		if($ex instanceof Exception)
			$ex = e\render_exception($ex);
		else
			$ex = '';

		// If not found show the form
		$title = "EvolutionSDK&trade; Edit Environment Variable";
		$header = '<span>EvolutionSDK</span> &rarr; <a href="/@manage">Manage System</a> &rarr; <a href="/@manage/environment">Environment</a> &rarr; <span>Edit Variable</span>';
		$css = file_get_contents(e\root.e\bundles.'/debug/theme.css');
		echo "<!doctype html><html><head><title>$title</title><style>$css</style></head><body class='_e_dump'><h1>$header</h1>";
		echo $this->requireForm($var, $value, $format, $why).$ex;
		echo "</body></html>";
		
		// Exit PHP
		exit;
	}
	
	public function route($path) {

		/**
		 * Security access for development
		 */
		e::$security->developerAccess();

		$form = array_shift($path);
		$var = array_shift($path);
		if(!in_array($form, array('edit', 'delete', 'update')))
			throw new Exception("Invalid environment variable action `$form`");
		
		$this->$form($var);
		e\complete();
	}
	
	public static function getAll() {
		return self::$environment;
	}
	
	public static function load() {
		// Check for file
		if(!is_file(self::$file) && !e::$yaml->save(self::$file, array()))
			throw new Exception("No environment file at `".self::$file."`");
		
		// Load environment file
		$tmp = e::$yaml->file(self::$file);
		foreach($tmp as $key => $value)
			self::$environment[strtolower($key)] = $value;
	}
	
	public function save() {
		// Save environment file
		ksort(self::$environment);
		e::$yaml->save(self::$file, self::$environment);
	}
	
	/**
	 * Editing functions
	 */
	private function requireForm($var, $value, $format, $why) {
		
		if(is_null(self::$url))
			self::$url = e::$router->url;
		
		$out = '<div class="section"><h1>Required Variable <code>'.htmlspecialchars($var).'</code> '.$why.'</h1>';
		$out.= '<p>Environment variables are saved separately on each server and are particular to the current system configuration.</p>';
		$out.= '<form action="/@environment/update" method="post"><h4>Set Variable</h4><div class="trace">';
		if(strlen($format) > 0)
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
			if($value === true)
				$value = 'true';
			else if($value === false)
				$value = 'false';
			$out .= '<textarea name="'.base64_encode($var).'">'.htmlspecialchars($value).'</textarea>';
		}
		
		$out.= '</div>';
		$out.= '<input type="submit" value="Save Environment Variable" /></div></form>';
		
		return $out;
	}
	
	public function edit($var) {
		$key = base64_decode($var);
		self::$url = '/@manage/environment';
		self::_require($key, self::$environment[$key.'---format'], 'can be edited!', true, null, false);
	}
	
	public function delete($var) {
		$key = base64_decode($var);
		self::$url = '/@manage/environment';
		unset(self::$environment[$key]);
		unset(self::$environment[$key.'---format']);
		$this->save();
		e\redirect(self::$url);
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
		$this->save();
		e\redirect($return);
	}
}