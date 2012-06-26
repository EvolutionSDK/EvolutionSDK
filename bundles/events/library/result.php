<?php

namespace Bundles\Events;
use Exception;
use stack;
use e;

class Result {
	private $type;
	public function __construct($type) {
		$this->type = $type;
	}

	public function __call($method, $args) {
		$result = call_user_func_array(array(e::$events,$method), $args);
		switch($this->type) {
			case 'first':
				if(empty($result))
					return null;
				return current($result);
			break;
			case 'last':
				if(empty($result))
					return null;
				return end($result);
			break;
			case 'single':
				if(empty($result))
					return null;
				elseif(count($result) > 1) 
					throw new Exception("You are running an event that only wants one result, multiple bundles are responding.");
				return current($result);
			break;
			case  'all':
				return $result;
			break;
		}
	}
}