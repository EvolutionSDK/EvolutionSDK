<?php

/**
 * Hack for apache_request_headers() and getallheaders() on nginx
 * @author Kelly Becker
 */
if (!function_exists('apache_request_headers')) {
	function apache_request_headers() {
		foreach($_SERVER as $key=>$value) {
			if (substr($key,0,5)=="HTTP_") {
				$key = str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", substr($key,5)))));
				$out[$key] = $value;
			} else $out[$key]=$value;
		}
		
		return $out;
	}
}

if(!function_exists('getallheaders')) {
	function getallheaders() {
		return apache_request_headers();
	}
}

/**
 * Hack for absense of Redirect URL
 * @todo find a better thing for this
 */
$uri = explode('?', $_SERVER['REQUEST_URI']);
$_SERVER['REDIRECT_URL'] = array_shift($uri);