<?php

namespace Bundles\Router;
use Bundles\Portal\Bundle as PortalBundle;
use Exception;
use stack;
use e;

class NotFoundException extends Exception {}

/**
 * Router Bundle
 * @author Nate Ferrero
 */
class Bundle {
	
	public $url;
	public $path;
	public $type;

	public function domainSegment($id = 0) {
		$segs = array_reverse(explode('.', $_SERVER['HTTP_HOST']));
		return isset($segs[$id]) ? $segs[$id] : null;
	}

	public function urlSegment($id = 0) {
		$segs = explode('/', trim($_SERVER['REDIRECT_URL'], '/'));
		return isset($segs[$id]) ? $segs[$id] : null;
	}
	
	public function _on_ready() {
		
		/**
		 * @todo Create URL bundle
		 */
		$url = $_SERVER['REDIRECT_URL'];

		$this->url = $url;
		
		/**
		 * Handle special URLs
		 */
		switch($url) {
			case '/humans.txt':
				return $this->humans();
		}
		
		$this->path = explode('/', $this->url);
		if($this->path[0] === '')
		    array_shift($this->path);
		if($this->path[count($this->path) - 1] === '')
		    array_pop($this->path);
		
		/**
		 * Router bundle access
		 */
		if(substr($url, 0, 2) == '/@')
			$this->route_bundle();
		
		e::$events->router_route($this->path);
	}

	/**
	 * Humans.txt handling
	 */
	public function humans() {
		header('Content-Type: text/plain');
		if(file_exists(stack::$site.'/humans.txt')) {
			readfile(stack::$site.'/humans.txt');
			echo "\n\n";
		}
		if(is_file(e\root.'/humans.txt'))
			readfile(e\root.'/humans.txt');
		exit;
	}
	
	public function route_bundle() {
		try {
			$path = $this->path;
			$bundle = substr(array_shift($path), 1);
			$realm = array_shift($path);
			
			if(preg_match('/[^a-zA-Z]/', $bundle))
				throw new Exception("Bundle name `@$bundle` contains invalid characters");
				
			if(!isset(e::$$bundle))
				throw new Exception("Bundle `@$bundle` does not exist");
			
			switch($realm) {
				case 'api':
					$this->route_bundle_api($bundle, $path);
					break;
				default:
					array_unshift($path, $realm);
					e::$$bundle->route($path);
					break;
			}
			
			throw new Exception("Bundle `@$bundle` routing did not complete");
		} catch(Exception $e) {
			PortalBundle::$currentException = $e;
			e\complete();
		}
	}
		
	
	public function route_bundle_api($bundle, $path) {
		
		$version = array_shift($path);
		$type = array_shift($path);
		
		if($type !== 'json')
			throw new Exception("API format `$type` is not a valid type");
		else {
			header("Content-type: application/json");
		}
		
		e\trace(__CLASS__, "API `$type` access for bundle `$bundle`");
		
		/**
		 * Wrap any exceptions
		 */
		try {
		
			if(strlen($bundle) === 0)
				throw new Exception("No bundle specified for routing after API access `@`");
			
			$class = "Bundles\\$bundle\\api\\$version";
			$result = new $class($path);
			
			e\trace(__CLASS__, "Processing API access with `".get_class($result)."`");
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
					echo $result;
					break;
				case 'json':
					echo json_encode($result);
					break;
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
					break;
				default:
					throw $exception;
			}
		}
		
		if(!isset($_GET['--debug']))
			e\disable_trace();
		e\complete();
	}
}