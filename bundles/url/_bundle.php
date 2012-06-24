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
	public $http_root = array();


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
	 * Analyze the url and see if we can find a controller or interface to run.
	 *
	 * @return void
	 * @author David D. Boskovic
	 */
	public function __initBundle() {

		# Setup path information
		$this->initialize();

		# Check for quick link to app
		if(substr($this->path, 0, 2) == '/~') {
			$d = explode('.', substr($this->segments[1], 1));
			$type = array_shift($d);
			$m1 = array_shift($d);
			$m2 = array_shift($d);

			if($type == 'app') {
				echo json_encode_safe(e::app($m1)->$m2());
				die;
			}
		}

		# Check for link
		if($this->path == '/--link-communicate') {
			return e::$com->link()->handle($_POST);
		}

		# load the current url configuration
		$urls = e::$configure->routing + e::$configure->routing_local;
		
		}
	}

	/**
	 * Last segment
	 */
	public function last() {
		$c = count($this->segments);
		return $this->segments[$c - 1];
	}


	



	/**
	 * Initialize the url handler.
	 *
	 * @return void
	 * @author David D. Boskovic
	 */
	public function initialize() {

		# initialize variables
		$this->path = $_SERVER['REQUEST_URI'];
		$this->referer = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		if(empty($this->referer)) {
			if(!empty(e::$session->data['redirect_url']))
				$this->referer = e::$session->data['redirect_url'];
		}

		$this->domain = $this->_parse_domain();
		$this->http_root = @e::$env['http_path'] ? $this->_parse_path(e::$env['http_path']) : false;
		$this->segments = $this->_parse_path();

		# define SUBDOMAIN
		$subDomain = !empty($this->domain[3]) ? $this->domain[3] : '';
		define('SUBDOMAIN', $subDomain);

		# Show debug information
		if(isset($_GET['--routing'])) {
			$segments = implode(' &bull; ', $this->segments);
			$domain = implode(' &bull; ', array_reverse($this->domain));
			$date = date('D F jS Y');
			$time = date('G:i e');
			echo "<h2>Evolution Routing Information</h2>
			<style>
				body, table {
					font-family: Helvetica, Tahoma, sans;
					font-size: 13px;
				}

				table {
					border-collapse: collapse;
				}

				th {
					border: 1px solid #888;
					background: #eee;
				}

				td, th {
					padding: 0.5em;
				}

				td {
					border: 1px solid #bbb;
				}
			</style>
			<table>
				<tr><th>Variable</th><th>Value</th></tr>
				<tr><td>Path</td><td>$this->path</td></tr>
				<tr><td>Referer</td><td>$this->referer</td></tr>
				<tr><td>Domain</td><td>$domain</td></tr>
				<tr><td>HTTP Root</td><td>$this->http_root</td></tr>
				<tr><td>Path Segments</td><td>$segments</td></tr>
				<tr><td>Subdomain</td><td>$subDomain</td></tr>
				<tr><td>Time</td><td>$date at $time</td></tr>
			</table>";
		}
	}


	public function position($no = null) {
		if(is_null($no)) return $this->pointer;
		else $this->pointer = $no;
	}


	public function next() {
		++$this->pointer;
	}


	public function prev() {
		--$this->pointer;
	}

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



	//Assigns all specially formatted GET variables ( /key:val/ ) to the $_GET superglobal
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

	public function _parse_path($uri = '') {

		if(empty($uri)) {
			$uri = $_SERVER['REQUEST_URI'];
			$ignore = $this->http_root;
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


	public function urdl($protocol = NULL) {
		if(is_null($protocol)) {
			$protocol = $this->protocol();
		}
		return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	}


	public function domain($prepend = '') {
		return $prepend.url::$domain[2].'.'.url::$domain[1];
	}

	public function protocol() {
		return ($_SERVER['HTTPS'] == 'on')? 'https' : 'http';
	}

	public function is_ajax() {
		return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest");
	}


	getSegment('2-4')
	getSegment('2$-1$')


}
