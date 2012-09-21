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
	public $referrer;

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
		 * Load in necessary variables.
		 * @todo remove dependency on Apache vars
		 */
		$this->path = $_SERVER['REQUEST_URI'];
		$this->referrer = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		$this->rawDomain = $_SERVER['HTTP_HOST'];

		/**
		 * Run an event and try and get the referer if applicable
		 * @todo clean this up, it's messy, and what happens if multiple bundles respond with an event.
		 */
		if(!$this->referrer) {
			$referrer = e::$events->first->get_referrer();
			$this->referrer = $referrer ? $refferer : false;
		}

		/**
		 * Parse domain and path
		 */
		$this->domain = $this->parse_domain($this->rawDomain);
		$this->segments = $this->parse_path($this->path);
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
	 * Parse any URI (can include query string)
	 *
	 * @author  David Boskovic
	 * @since   06/24/2012
	 *
	 * @param   string [$uri]
	 * @example /path/string/stuff?foo=bar => [1=> 'path', 2=> 'string', 3=> 'stuff']
	 * @return  array
	 */
	public function parse_path($uri) {

		/**
		 * Strip away the GET string from the URI if it's around.
		 * @todo Do something with the GET string here eventually, or completely deprecate it.
		 */
		list($path, $get) = explode('?', $uri);

		/**
		 * trim the path to make sure there's not a hanging empty array item after exploding...
		 * however the path string is expected to always start with / so we add it back
		 */
		$path = explode("/", '/'.trim($path,'/'));

		/**
		 * Because the string starts with a "/", array[0] is empty. This is important because
		 * we actually want array 1 to be the first var
		 * @todo I think it would be better to have a proper array here and +1 to the pointer later
		 */
		unset($path[0]);		

		/**
		 * Return the parsed array of segments.
		 */
		return $path;
	}



	/**
	 * Parse the domain
	 */
	public function parse_domain($domain) {
		$array = explode('.', $domain);
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
	 * Redirect to a new URL. Stops all processing and sends user to a new URL.
	 *
	 * @author  David Boskovic
	 * @since   06/24/2012
	 *
	 * @example e::$url->redirect('/foo/bar') => 'http://www.domain.com/foo/bar'
	 * @example e::$url->redirect('/foo/bar', 'https') => 'https://www.domain.com/foo/bar'
	 * @example e::$url->redirect('http://google.com') => 'http://google.com'
	 *
	 * @param   string [$to], string [$protocol] http|https
	 * @return  null
	 *
	 * @todo add support for output buffer.
	 * @todo add checking for proper format of $to param
	 * @todo add support for session transferring with an SID get var
	 */
	public function redirect($to, $protocol = false)	{

		/**
		 * Build the domain.
		 */
		$url = $this->link($to, $protocol);

		/**
		 * Do a header redirect as long as the headers have not yet been sent.
		 */
		if (!headers_sent())
	        header('Location: '.$url);
	   
	    /**
	      * If headers are sent... do javascript redirect... if javascript disabled, do html redirect.
	      * This code will get sent anyways, in case for some reason the browser won't header-redirect.
	      */ 
        echo '<script type="text/javascript"> window.location.href="'.$url.'"; </script>';
        echo '<noscript> <meta http-equiv="refresh" content="0;url='.$url.'" /> </noscript>';
        echo "If this page does not redirect, <a href=\"$url\">click here</a> to continue.";

	    /**
	     * Kill the script. But give bundles a chance to execute critical function before death.
	     * @todo this should probably be passed to a kernel method for killing scripts
	     */
	    e::$events->force_quit('redirect');
	    die;
	}

	/**
	 * Generate a proper link based off a root path eg: /foo/bar
	 *
	 * @author  David Boskovic
	 * @since   06/24/2012
	 *
	 * @example e::$url->link('/foo/bar') => 'http://www.domain.com/foo/bar'
	 * @example e::$url->link('/foo/bar', 'https') => 'https://www.domain.com/foo/bar'
	 * @example e::$url->link('http://google.com') => 'http://google.com'
	 *
	 * @param   string [$to], string [$protocol] http|https
	 * @return  string
	 *
	 * @todo add support for a special secure subdomain to be configured for https links
	 * @todo clean up and document better
	 */
	public function link($src, $protocol = false) {

		/**
		 * Build the domain.
		 */
		if(strpos($src, '://') === FALSE) {

			if($protocol && !($protocol == 'http' || $protocol == 'https'))
					throw new Exception('If you pass a protocol, it must either be `http` or `https`. Otherwise do not pass a protocol to the redirect function.');
			else $protocol = $this->protocol();

			$url = $protocol. '://' . $this->domain() . '/'.ltrim($src,'/');
		} 
		else $url = $src;

		return $url;
	}



	/**
	 * Return the full raw domain. Add Subdomain if desired.
	 *
	 * @author  David Boskovic
	 * @since   06/24/2012
	 *
	 * @example e::$url->domain('my') => 'my.domain.com'
	 * @param   string [$subdomain]
	 * @return  string
	 */
	public function domain($subdomain = false, $honor_full_domain = false) {

		/**
		 * Return the full domain + subdomain. If you want to prepend the subdomain to the entire
		 * domain, make sure to pass the second param as true. (Warning this could result in 
		 * returning 'subdomain.www.domain.com' rather than 'subdomain.domain.com'!!!)
		 */
		if(is_numeric($subdomain)) {
			$level = (int) $subdomain;
			$domain = array();
			for($i=1;$i<=$level;$i++) {
				$domain[] = $this->domain[$i];
			}
			$domain = array_reverse($domain);
			$domain = implode('.',$domain);
			return $domain;
		}
		if(is_string($subdomain) && strlen($subdomain) > 0) {
			if($honor_full_domain)
				return rtrim($subdomain, '.') . '.' . $this->rawDomain;
			else
				return implode('.', array($subdomain, $this->domain[2], $this->domain[1]));
		}

		/**
		 * Return the current raw domain, whatever it is.
		 */
		else
			return $this->rawDomain;
	}



	/**
	 * Return the full path up to whatever pointer is specified.
	 *
	 * @author  David Boskovic
	 * @since   06/24/2012
	 *
	 * @example e::$url->path() => '/this/domain/is/awesome'
	 * @example e::$url->path(0) => '/this/domain/is/awesome'
	 * @example e::$url->path(1) => '/this'
	 * @example e::$url->path(2) => '/this/domain'
	 * @example e::$url->path(3) => '/this/domain/is'
	 *
	 * @param   integer [$pointer]
	 * @return  string
	 */
	public function path($pointer = false) {
		if(is_numeric($pointer) && $pointer > 0)
			return $this->trace((int) $pointer);
		return '/'.implode('/', $this->segments);
	}

	/**
	 * Get the server protocol
	 * @todo verify NGINX also populates this SERVER var.
	 */
	public function protocol() {
		if($_SERVER['HTTP_X_FORWARDED_PORT'] == '80') return 'http';
		if($_SERVER['HTTP_X_FORWARDED_PORT'] == '443') return 'https';
		return ($_SERVER['HTTPS'] == 'on')? 'https' : 'http';
	}

	/**
	 * Is the active page load triggered by AJAX
	 * @todo verify NGINX also populates this SERVER var.
	 */
	public function is_ajax() {
		return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest");
	}

}
