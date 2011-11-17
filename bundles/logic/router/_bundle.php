<?php

namespace bundles\router;
use Exception;
use e;

class Bundle {
	
	public $url;
	public $path;
	public $type;
	
	public function _on_route($url) {
		
		$this->url = $url;
		
		$this->path = explode('/', $this->url);
		if($this->path[0] === '')
		    array_shift($this->path);
		if($this->path[count($this->path) - 1] === '')
		    array_pop($this->path);
		
		/**
		 * Router bundle access
		 */
		if(substr($url, 0, 2) == '/@')
			$this->api();
		
		e::events()->router_route($this->path);
	}
	
	public function api() {
		$path = $this->path;
		$tmp = array_shift($path);
		$tmp = explode('.', substr($tmp, 1));
		if(count($tmp) < 2)
			$tmp[] = 'plain';
		if(count($tmp) < 3)
			$tmp[] = 'v1';
		
		list($bundle, $type, $version) = $tmp;
		e\trace(__CLASS__, "API access `$type` for bundle `$class`");
		
		/**
		 * Wrap any exceptions
		 */
		try {
		
			if(strlen($bundle) === 0)
				throw new Exception("No bundle specified for routing after API access `@`");
			
			$class = "Bundles\\$bundle\\api\\$version";
			$result = new $class($path);
			
			e\trace(__CLASS__, "Processing API access with `".get_class($api)."`");
			foreach($path as $segment) {
				/**
				 * Null
				 */
				if(is_null($result))
					break;
				
				/**
				 * Handle String access
				 */
				if(is_string($result)) {
					$subs = explode(',', $segment);
					$temp = '';
					foreach($subs as $sub) {
						$sub = explode('-', $sub);
						if(count($sub) == 1)
							$sub[] = $sub[0];
						$temp .= substr($result, $sub[0], $sub[1] - $sub[0] + 1);
					}
					$result = $temp;
				}
		
				/**
				 * Handle Array access
				 */
				else if(is_array($result)) {
					if(isset($result[$result]))
						$result = $result[$result];
					else
						$result = null;
				}
		
				/**
				 * Handle Object access
				 */
				else if(is_object($result)) {
					if(isset($result->{$segment}))
						$result = $result->{$segment};
					else if(method_exists($result, $segment))
						$result = $result->$segment();
					else
						$result = null;
				}
			}
			
			/**
			 * API output
			 */
			switch($type) {
				case 'plain':
					if(is_array($result))
						foreach($result as $r)
							echo $r;
					else
						echo $result;
					break;
				case 'json':
					echo json_encode($result);
					exit;
				default:
					throw $exception;
			}
		}
		catch(Exception $exception) {
			if(isset($_GET['--debug']))
				throw $exception;
			
			/**
			 * Format exception for API
			 */
			switch($type) {
				case 'plain':
					throw $exception;
				case 'json':
					echo json_encode(array('exception' => $exception->getMessage()));
					exit;
				default:
					throw $exception;
			}
		}
		
		e\complete();
	}
}