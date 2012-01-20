<?php

namespace Bundles\Unit\API;
use Exception;
use e;

/**
 * Unit Test API Version 1
 * @author Nate Ferrero
 */
class v1 {
	public function __isset($var) {
		return true;
	}
	public function __get($bundle) {
		return new BundleTester($bundle);
	}
}

/**
 * API Class Test Access
 * @author Nate Ferrero
 */
class BundleTester {
	public $bundle;
	public function __construct($bundle) {
		$this->bundle = $bundle;
	}
	public function __isset($var) {
		return true;
	}
	public function __get($method) {
		$all = e::$unit->getTests($this->bundle);
		foreach($all as $test) {
			if($test->_method() == $method)
				return $test->_run();
		}
		throw new Exception('No matching unit test found');
	}
}