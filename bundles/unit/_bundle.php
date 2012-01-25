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
	
	public function _method() {
		return $this->method;
	}
	
	public function _run() {
		$success = true;
		$exception = null;
		$test = null;
		$flaw = null;
		
		if(!method_exists($this->instance, $this->method))
			throw new Exception("Unit test method `$method` is not defined in class `".get_class($this->instance)."`");
		
		try {
			$call = $this->method;
			$test = $this->instance->$call();
		} catch(Exception $exception) {}
		
		foreach($this->conditions as $condition) {
			foreach($condition as $method => $args) {
				$value = array_shift($args);
				
				$func = "\\Bundles\\Unit\\test_$method";
				
				if(!function_exists($func))
					throw new Exception("The unit test method `$func` is not defined");
				
				$check = $func($test, $value, $args, $exception);
				
				if(!$check) {
					$flaw = $method;
					$success = false;
					break 2;
				}
			}
		}
		
		return array('method' => $this->method, 'result' => ($success ? 'pass' : 'fail'), 'value' => $test, 'exception' => $exception, 'flaw' => $flaw, 'comparison' => $value);
	}
}

/**
 * Unit test validation functions
 * @author Nate Ferrero
 */
function test_equals($a, $b) {
	return $a == $b;
}
function test_strictEquals($a, $b) {
	return $a === $b;
}
function test_greaterThan($a, $b) {
	return $a > $b;
}
function test_lessThan($a, $b) {
	return $a < $b;
}
function test_greaterThanOrEqual($a, $b) {
	return $a >= $b;
}
function test_lessThanOrEqual($a, $b) {
	return $a <= $b;
}
function test_between($a, $b, $args) {
	$c = array_shift($args);
	return $a > $b && $a < $c;
}
function test_throws($a, $b, $args, $ex) {
	return is_a($ex, $b);
}
function test_stringContains($a, $b) {
	return strpos($a, $b) !== false;
}
function test_instanceOf($a, $b) {
	return $a instanceof $b;
}