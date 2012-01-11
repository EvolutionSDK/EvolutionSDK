<?php

/**
 * Unit Test Bundle
 * @author Nate Ferrero
 */

namespace Bundles\Unit;
use Exception;
use e\AutoLoadException;
use e;

class Bundle {
	
	private $tests;
	private $instance;
	
	public function _on_framework_loaded() {
		
		// Add manager
		e::configure('manage')->activeAddKey('bundle', __NAMESPACE__, 'unit');
	}
	
	public function clearTests() {
		$this->tests = array();
		$this->instance = null;
	}
	
	public function test($method) {
		if(is_null($this->instance))
			throw new Exception("Unit tests cannot be created except within the `tests` method of a `Bundles\\<em>bundle-name</em>\\Unit` class");
		$test = new Test($method, $this->instance);
		$this->tests[] = &$test;
		return $test;
	}
	
	public function getTests($bundle) {
		$this->clearTests();
		$class = "Bundles\\$bundle\\Unit";
		try {
			$this->instance = new $class;
			$this->instance->tests();
			return $this->tests;
		} catch(AutoLoadException $e) {
			return array();
		}
	}

}

/**
 * Unit Test Class
 * @author Nate Ferrero
 */
class Test {
	private $method;
	private $instance;
	public $description;
	private $conditions = array();
	
	public function __construct($method, &$instance) {
		$this->method = $method;
		$this->instance = $instance;
	}
	
	public function description($description) {
		$this->description = $description;
		return $this;
	}
	
	public function __call($method, $args) {
		$this->conditions[] = array($method => $args);
		return $this;
	}
	
	public function run() {
		;
	}
}