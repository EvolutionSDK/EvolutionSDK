<?php

namespace Bundles\cURL;
use ReflectionClass;
use Exception;
use e;

class Bundle {

	public function __bundle_response() {
		$args = func_get_args();
		$class = new ReflectionClass(__NAMESPACE__.'\\cURL');
		return $class->newInstanceArgs($args);
	}
	
}