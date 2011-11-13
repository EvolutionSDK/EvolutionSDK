<?php

namespace bundles\yaml;
use e;

/**
 * SPYC YAML Parser.
 *
 * @package default
 * @author David D. Boskovic
 */
class DSpyc {
	private static $timeOffset = null;
	
	public function string($string) {
		$key = md5($string);
		# load the environments
		if(e::cache()->check('yaml-cache', $key)) {
			$result = e::cache()->get('yaml-cache', $key);
		}
		else {		
			$result = Spyc::YAMLLoadString($string);
			e::cache()->store('yaml-cache', $key, $result, 'base64');
		}
		return $result;	
	}
	protected function _get_configuration($file) {
		return Spyc::YAMLLoad($file);
	}

	protected function _get_configuration_time($file) {
			/*if(is_null(self::$timeOffset)) {
				$test = ROOT_CACHE . '/time';
				
				if(!file_exists($test))
					file_put_contents($test, time());
				
				$realTime = (int) file_get_contents($test);
				$reportedTime = (int) @filemtime($test);
				
				self::$timeOffset = $realTime - $reportedTime;
			}
			/* Uncomment above to enable strict cache time checking */
			return @filemtime($file) + self::$timeOffset;
	}
	
	public function file($file) {
		$key = md5($file);
		# load the environments
		if(e::cache()->check('yaml-cache', $key)) {
			$result = e::cache()->get('yaml-cache', $key);
		}
		$fileTime = $this->_get_configuration_time($file);
		$cacheTime = e::cache()->timestamp('yaml-cache', $key);
		
		if($fileTime > $cacheTime) {
			$result = $this->_get_configuration($file);
			e::cache()->store('yaml-cache', $key, $result, 'base64');
		}
		return $result;
		
	}
	
	public function is_changed($file) {
		$key = md5($file);
		# load the environments
		$fileTime = $this->_get_configuration_time($file);
		$cacheTime = e::cache()->timestamp('yaml-cache', $key);
		
		if($fileTime > $cacheTime || $cacheTime === false) {
			$result = $this->_get_configuration($file);
			e::cache()->store('yaml-cache', $key, $result, 'base64');
			
			return true;
		}
		
		return false;
	}
	
	public function last_modified($file) {
		return array(
			'yaml' => $this->_get_configuration_time($file),
			'cache' => e::cache()->timestamp('yaml-cache', md5($file))
		);
	}
	
	public function save($file, $array) {
		# get the string to save to the file
		if(!is_writable(dirname($file))) {
			throw new \Exception("Can't write YAML file for saving: `$file`");
		} else {
			$fh = fopen($file, 'w');
			if(!$fh) throw new \Exception("Can't open YAML file for saving: `$file`");
			fwrite($fh, $this->dump($array));
			fclose($fh);
			return true;
		}
	}
	public function dump($array, $indent = 4, $wordwrap = 0, $forcequotes = false) {
		return Spyc::YAMLDump($array, $indent, $wordwrap, $forcequotes);
	}
}

/**
   * Spyc -- A Simple PHP YAML Class
   * @version 0.4.5
   * @author Vlad Andersen <vlad.andersen@gmail.com>
   * @author Chris Wanstrath <chris@ozmm.org>
   * @link http://code.google.com/p/spyc/
   * @copyright Copyright 2005-2006 Chris Wanstrath, 2006-2009 Vlad Andersen
   * @license http://www.opensource.org/licenses/mit-license.php MIT License
   * @package Spyc
   */

if (!function_exists('spyc_load')) {
  /**
   * Parses YAML to array.
   * @param string $string YAML string.
   * @return array
   */
  function spyc_load ($string) {
    return Spyc::YAMLLoadString($string);
  }
}

if (!function_exists('spyc_load_file')) {
  /**
   * Parses YAML to array.
   * @param string $file Path to YAML file.
   * @return array
   */
  function spyc_load_file ($file) {
    return Spyc::YAMLLoad($file);
  }
}