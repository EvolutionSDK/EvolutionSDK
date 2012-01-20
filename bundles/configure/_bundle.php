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
	
	public function __callBundle($path, $load = true) {
		if(!isset($this->configs[$path]))
			$this->configs[$path] = new Configuration($path, $load);
		return $this->configs[$path];
	}
	
	public function _on_configuration_load($path) {
		$path = str_replace('.', '/', $path);
		$file = e\site . '/configure/' . $path . '.yaml';
		return array(
			$file => e::$yaml->file($file)
		);
	}
	
	public function _on_configuration_save($path, $value) {
		$path = str_replace('.', '/', $path);
		$file = e\site . '/configure/' . $path . '.yaml';
		return e::$yaml->save($file, $value);
	}
	
}

class Configuration {
	private $data = array();
	private $active = array();
	
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
	
	public function __get($var) {
		if(isset($this->active[$var]))
			return $this->active[$var];
		if(!isset($this->data[$var]))
			return null;
		return $this->data[$var];
	}
	
	public function __set($var, $value) {
		return $this->data[$var] = $value;
	}
	
	public function activeAddKey($var, $key, $value) {
		if(!isset($this->active[$var]))
			$this->active[$var] = array();
		$this->active[$var][$key] = $value;
	}
	
	public function activeAdd($var, $value) {
		if(!isset($this->active[$var]))
			$this->active[$var] = array();
		$this->active[$var][] = $value;
	}
	
	public function activeGet($var) {
		return isset($this->active[$var]) || !empty($this->active[$var]) ? $this->active[$var] : null;
	}
}