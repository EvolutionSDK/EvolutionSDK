<?php

namespace Bundles\URL;
use Exception;
use e;

class Bundle  {

	/**
	 * The requested url, eg: /something/else/now
	 *
	 * @var string
	 */
	private $path = '';

	/**
	 * The domain, broken up into an array, eg: array(1 => 'com', 2 => "domain", 3 => 'www')
	 *
	 * @var array
	 */
	private $domain = array();

	/**
	 * The domain. String format
	 *
	 * @var string
	 */
	private $rawDomain = '';

	/**
	 * An array of segments, eg: /something/else/now = array(0 => 'something', 1 => 'else', 2 => 'now')
	 *
	 * @var string
	 */
	public $segments = array();

	/**
	 * Referrer URL
	 *
	 * @var string
	 */
	public $referer;

	/**
	 * Current Pointer
	 *
	 * @var integer
	 */
	public $pointer = 1;


	/**
	 * Analyze the url and parse out information
	 * @author David D. Boskovic
	 * @author Kelly Becker
	 */
	public function __initBundle() {

		/**
		 * Initialize Request URI and referer
		 */
		$this->path = $_SERVER['REQUEST_URI'];
		$this->referer = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

		/**
		 * Run an event and try and get the referer if applicable
		 */
		if(empty($this->referer)) foreach(e::$events->get_referer() as $refer) {
			if(!empty($refer)) {
				$this->referer = $refer;
				break;
			}
		}

		/**
		 * Parse domain and path
		 */
		$this->domain = $this->_parse_domain();
		$this->segments = $this->_parse_path();
		$this->_parse_get();
	}

	/**
	 * Last segment
	 */
	public function last() {
		$c = count($this->segments);
		return $this->segments[$c - 1];
	}

	/**
	 * Set/Return the pointer
	 */
	public function position($no = null) {
		if(is_null($no)) return $this->pointer;
		return $this->pointer = $no;
	}

	/**
	 * Advance the pointer
	 */
	public function next() {
		++$this->pointer;
	}

	/**
	 * Decrease the pointer
	 */
	public function prev() {
		--$this->pointer;
	}

	/**
	 * Output the path as loaded so far
	 */
	public function trace($no = false) {
		$no = $no !== false ? $no : $this->pointer;

		if($no < 0) {
			$no = $no / -1;	// invert sign
			$no = $this->pointer - $no;
		}

		$path = '/';
		foreach($this->segments as $key => $val) {
			if($key >= $no) { break; } else {
				$path .= $val.'/';
			}
		}
		return $path;
	}

	/**
	 * Get a segment, or list of segments
	 */
	public function segment($pointer = false) {

		if(is_numeric($pointer)) $pointer = (int) $pointer;
		# if no pointer has been specified, get the current pointer
		$pointer = $pointer ? $pointer : $this->pointer;

		# string "+integer"
		if( is_string($pointer) && substr($pointer, 0, 1) == '+') {
			$pointer = $this->pointer + str_replace('+', '', $pointer);
			return $this->segments[$pointer];
		}

		# string
		elseif( is_string($pointer) ) {
			$labels = array_flip($this->labels);
			$pointer = $labels[$pointer];
				return $this->segments[$pointer];
		}

		# integer (negative)
		elseif( $pointer < 0 ) {
			$pointer = $this->pointer + $pointer;
			return $this->segments[$pointer];

		}

		# integer (positive)
		else return isset($this->segments[$pointer]) ? $this->segments[$pointer] : false;
	}

	/**
	 * Label a segment
	 */
	public function Label($string, $condition = TRUE) {
		if($condition == FALSE) return FALSE;

		$labels = explode("/", $string);
		$output = array();
		if(substr($string, 0, 1) == '/') {
			unset($labels[0]);
			foreach($labels as $key => $val) {
				$this->labels[$key] = $val;
			}
		} else {
			foreach($labels as $key => $val) {
				$this->labels[$key + $this->pointer] = $val;
			}
		}
	}

	/**
	 * Parse the GET Variables
	 */
	public function _parse_get() {
		$pathComponents = explode('/', $this->path);
		foreach($pathComponents as $val){
			if(stripos($val, ':') !== FALSE){
				$getParts = explode(':', $val);
				$getKey = $getParts[0];
				$getVal = $getParts[1];

				//If query string is formatted like:  /url?key:val, then parse out the url
				if( stripos($getParts[0], '?') !== FALSE){
					$getKey = substr($getKey, stripos($getKey, '?')+1 );
				}

				//If query string is formatted like:  /url?key:val, then this key will be set: $_GET['key:val'].  Let's unset it.
				/* Commented out because there is potential for errors (ie, $GET keys getting deleted)
				if( isset($_GET[$getKey]) )
					unset($_GET[$getKey]);*/

				$_GET[$getKey] = $getVal;
			}
		}
	}

	/**
	 * Parse the path
	 */
	public function _parse_path($uri = '') {

		if(empty($uri)) {
			$uri = $_SERVER['REQUEST_URI'];
		}
		else {
			$ignore = false;
		}

		$pathComponents = explode('?', $uri);
		if(empty($pathComponents[1])) $pathComponents[1] = '';

		list($path, $get) = $pathComponents;
		$path = explode("/", $path);

		$path = array_reverse($path);


		if($path[0] == '') {
			unset($path[0]);
		}
		$path = array_reverse($path);

		unset($path[0]);
		if($ignore) {
			$npath = array_diff_assoc($path, $ignore);
			array_unshift($npath, 'del');
			unset($npath[0]);
			$path = $npath;
		}

		return $path;
	}

	/**
	 * Parse the domain
	 */
	public function _parse_domain() {
		$this->rawDomain = $_SERVER['HTTP_HOST'];
		$array = explode('.', $_SERVER['HTTP_HOST']);
		$array = array_reverse($array);
		$pointer = 1;

		foreach($array as $domain) {
			$output[$pointer] = $domain;
			++$pointer;
		}

		// get the port if included in url
		$httpHost = explode(':',$_SERVER['HTTP_HOST']);
		if(empty($httpHost[1])) $httpHost[1] = ''; //Prevents port from being undefined on next line

		list($domain, $port) = $httpHost;
		$output['1'] = $port ? $output['1'].':'.$port : $output['1'];
		$this->rawDomain = $port ? $this->rawDomain.":".$port : $this->rawDomain;
		return $output;
	}

	/**
	 * Redirect Function
	 */
	public function redirect($to)	{

		//ob_end_clean();

		if(stripos($to, '://') === FALSE) {
			$url = $this->protocol(). '://' . $this->rawDomain . $this->Link($to);
		} else {
			$url = $to;
		}

		if (!headers_sent()){    // If headers not sent yet... then do php redirect
	        header('Location: '.$url);
	        echo "If this page does not redirect, <a href=\"$url\">click here</a> to continue";
	    } else {                 // If headers are sent... do javascript redirect... if javascript disabled, do html redirect.
	        echo '<script type="text/javascript">';
	        echo 'window.location.href="'.$url.'";';
	        echo '</script>';
	        echo '<noscript>';
	        echo '<meta http-equiv="refresh" content="0;url='.$url.'" />';
	        echo '</noscript>';
	    }

	    die;
	}

	/**
	 * Get a copy of the url to the current page with current domain, and protocol
	 */
	public function urdl($protocol = NULL) {
		if(is_null($protocol)) {
			$protocol = $this->protocol();
		}
		return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	}

	/**
	 * Get the raw domain
	 */
	public function domain($prepend = '') {
		return $prepend.$this->rawDomain;
	}

	/**
	 * Get the raw path
	 */
	public function path() {
		return $this->path;
	}

	/**
	 * Get the server protocol
	 */
	public function protocol() {
		return ($_SERVER['HTTPS'] == 'on')? 'https' : 'http';
	}

	/**
	 * Is the active page load triggered by AJAX
	 */
	public function is_ajax() {
		return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest");
	}

}
