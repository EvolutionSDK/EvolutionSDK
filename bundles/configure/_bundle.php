<?php

namespace Bundles\Configure;
use Exception;
use stack;
use e;

/**
 * Configure Bundle
 */
class Bundle {
	
	private $configs = array();
	
	/**
	 * Return configuration instance
	 * Second attribute should be false if you need to prevent loading of files
	 * @author Nate Ferrero
	 */
	public function __callBundle($path, $load = true) {
		if(!isset($this->configs[$path]))
			$this->configs[$path] = new Configuration($path, $load);
		return $this->configs[$path];
	}
	
	/**
	 * Load configuration files
	 * @author Nate Ferrero
	 */
	public function _on_configuration_load($path) {
		$path = str_replace('.', '/', $path);
		$file = e\site . '/configure/' . $path . '.yaml';
		return array(
			$file => e::$yaml->file($file)
		);
	}
	
	/**
	 * Save configuration files
	 * @author Nate Ferrero
	 */
	public function _on_configuration_save($path, $value) {
		$path = str_replace('.', '/', $path);
		$file = e\site . '/configure/' . $path . '.yaml';
		return e::$yaml->save($file, $value);
	}
	
}

/**
 * Configuration instance
 * @author Nate Ferrero
 */
class Configuration {
	private $data = array();
	private $active = array();
	
	/**
	 * Construct the instance
	 * @author Nate Ferrero
	 */
	public function __construct($path, $load = true) {
		if(!$load) return;
		
		$all = e::$events->configuration_load($path);
		foreach($all as $config) {
			foreach($config as $location => $item) {
				$this->data = $item;
				/**
				 * @todo Merge items instead of only using the first one
				 */
				break;
			}
		}
	}
	
	/**
	 * Get a configuration variable
	 * @author Nate Ferrero
	 */
	public function __get($var) {
		if(isset($this->active[$var]))
			return $this->active[$var];
		if(!isset($this->data[$var]))
			return null;
		return $this->data[$var];
	}
	
	/**
	 * Set a configuration variable
	 * @author Nate Ferrero
	 */
	public function __set($var, $value) {
		return $this->data[$var] = $value;
	}

	/**
	 * Check if a configuration variable exists
	 * @author Nate Ferrero
	 */
	public function __isset($var) {
		if(isset($this->active[$var]))
			return true;
		return isset($this->data[$var]);
	}
	
	/**
	 * Active add a configuration key
	 * @author Nate Ferrero
	 */
	public function activeAddKey($var, $key, $value) {
		if(!isset($this->active[$var]))
			$this->active[$var] = array();
		$this->active[$var][$key] = $value;
	}
	
	/**
	 * Active add a configuration variable
	 * @author Nate Ferrero
	 */
	public function activeAdd($var, $value) {
		if(!isset($this->active[$var]))
			$this->active[$var] = array();
		$this->active[$var][] = $value;
	}
	
	/**
	 * Only get active configuration variable
	 * Use in situations where loading from the configuration files is not possible
	 * @author Nate Ferrero
	 */
	public function activeGet($var) {
		return isset($this->active[$var]) || !empty($this->active[$var]) ? $this->active[$var] : null;
	}
}