<?php

Namespace bundles\cache;
use Exception;
use stack;
use e;

/**
 * Manages the PHP variable cache.
 *
 * @package default
 * @author David D. Boskovic
 */
class Bundle {
	
	public $cache = array();
	public $cache_update_time = array();
	public $check_record = array();
		
	private $_cache_path;
	
	public function __construct($dir) {

		/**
		 * Set the cache path
		 * @author Kelly Becker
		 */
		$this->_cache_path = e\siteCache;

		try {
			if(!is_dir($this->_cache_path)) exec('mkdir -p -m 0777 '.$this->_cache_path);
		} catch(Exception $e) {
			throw new Exception("Could not create cache folder at `$this->_cache_path`", 0, $e);
		}
		try {
			if(!is_writable($this->_cache_path)) exec('chmod -Rf 0777 '.$this->_cache_path);
		} catch(Exception $e) {
			throw new Exception("Could not make cache folder writable at `$this->_cache_path`", 0, $e);
		}
	}
	
	public function path() {
		return $this->_cache_path;
	}
	
	/**
	 * Check the library to see if there's a cached value for the requested variable.
	 *
	 * @param string $library 
	 * @param string $key 
	 * @return boolean
	 * @author David D. Boskovic
	 */
	public function check($library, $key, $force_reload = false) {
		$mkey = md5($key);
		if(isset($this->cache[$library][$key]) && !$force_reload) {
			return true;
		}
		elseif(file_exists($this->_cache_path."/$library/$mkey.cache")) {
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Checks the latest timestamp recursively within a folder
	 * @author Nate Ferrero
	 */
	public function contentsModifiedTimestamp($path) {
		$timestamp = 0;
		$dir = opendir($path);
		if($dir !== false) {

			while(($item = readdir($dir)) !== false) {
				if($item === '.' || $item === '..')
					continue;
				$item = "$path/$item";
				$timestamp = max($timestamp, filemtime($item));
				if(is_dir($item))
					$timestamp = max($timestamp, $this->contentsModifiedTimestamp($item));
			}

		}
		closedir($dir);
		return $timestamp;
	}
	
	public function timestamp($library, $key) {
		$mkey = md5($key);
		if(!$this->check($library, $key))
			return false;
		else
			return filemtime($this->_cache_path."/$library/$mkey.cache");
	}
	
	/**
	 * Get the value of a cached variable. Returns NULL if the variable is not cached.
	 *
	 * @param string $library 
	 * @param string $key 
	 * @return mixed
	 * @author David D. Boskovic
	 */
	public function get($library, $key, $force_reload = false) {
		if($this->check($library, $key)) {
			if($this->_is_loaded($library, $key) && !$force_reload)
				return unserialize($this->cache[$library][$key]);
			else
				return $this->_load($library, $key);
		}
		else
			return NULL;		
	}
	
	public function _is_loaded($library, $key) {
		return isset($this->cache[$library][$key]);
	}
	
	public function _load($library, $key) {
		$mkey = md5($key);
		if($this->check($library, $key)) {
			$data = file_get_contents($this->_cache_path."/$library/$mkey.cache");
			$data = base64_decode($data);
			$this->cache[$library][$key] = $data;
			return unserialize($data);
		}
		else return NULL;
	}
	
	/**
	 * Save a value to memory and the cache file.
	 *
	 * @param string $library 
	 * @param string $key 
	 * @param string $value 
	 * @return boolean
	 * @author David D. Boskovic
	 */
	public function store($library, $key, $value, $encrypt = false) {
		e\trace('Cache', "Storing `$key` in `$library`");
		
		# make sure the current library values are loaded
		$this->check($library, $key);

		$value = serialize($value);
		
		switch($encrypt) {
			default:
				# get base64string
				$save_value = wordwrap(base64_encode($value), 120, "\n", true);
			break;
		}
		$this->cache[$library][$key] = $value;
		return $this->write($library, $key, $save_value);
	}
	
	/**
	 * Delete a value from memory and the cache file.
	 *
	 * @param string $library 
	 * @param string $key 
	 * @param string $value 
	 * @return boolean
	 * @author David D. Boskovic
	 */
	public function delete($library, $key) {
		
		# make sure the current library values are loaded
		if(!$this->check($library, $key)) return true;
		$mkey = md5($key);
		$file = $this->_cache_path."/$library/$mkey.cache";
		unlink($file);
		return true;
	}
	
	/**
	 * Handle the actual writing of the cache file.
	 *
	 * @param string $library 
	 * @return void
	 * @author David D. Boskovic
	 */
	private function write($library, $key, $string) {
		$mkey = md5($key);
		
		# get the string to save to the file
		if(!is_writable($this->_cache_path."/")) {
			throw new \Exception("Cache folder is not writable, execute command `sudo mkdir $this->_cache_path; sudo chmod 777 $this->_cache_path;`");
		} else {
			if(!is_dir($this->_cache_path."/$library")) {
				mkdir($this->_cache_path."/$library");
			}
			$file = $this->_cache_path."/$library/$mkey.cache";
			$fh = fopen($file, 'w');
			if(!$fh) throw new \Exception("Can't open cache file for saving: $file");
			fwrite($fh, $string);
			fclose($fh);
			return true;
		}
	}
	
	private function _decrypt($library, $key) {
		$working_copy = $this->cache[$library][$key];
		$fv = strpos($working_copy, '|');
		$conf = substr($working_copy, 0, $fv);
		$working_copy = substr($working_copy, $fv);
		$r = explode(':', $conf);
		
		switch($r[1]) {
			case 'base64' :
				return unserialize(base64_decode($working_copy));
			break;
		}
	}
	
	private function _timestamp_segment($library, $key) {
		if($this->check($library,$key))
			$this->cache_update_time[$library][$key] = microtime(true);
		$string = '';
		if(is_array($this->cache_update_time))
			foreach($this->cache_update_time[$library] as $k => $time) {			
				$human_readable = date('M d, Y H:i:s e (\G\M\T P)',$time);
				$string .= "# last update $human_readable\n".'$_timestamp["'.$k.'"] = '."$time;\n\n";
			}
		return "# ---------------------------------------------------\n\n".$string;
	}
	
}