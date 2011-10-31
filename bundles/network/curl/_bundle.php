<?php

namespace bundles\cURL;

class Bundle {
	var $headers; 
	var $user_agent; 
	var $compression; 
	var $cookie_file; 
	var $proxy; 
	
	public function __construct($dir) { 
		$this->headers[] = 'Accept: image/gif, image/x-bitmap, image/jpeg, image/pjpeg'; 
		$this->headers[] = 'Connection: Keep-Alive'; 
		$this->headers[] = 'Content-type: application/x-www-form-urlencoded;charset=UTF-8'; 
		$this->user_agent = 'Evolution - E3'; 
		$this->compression = 'gzip'; 
		$this->proxy = ''; 
		$this->cookies = $dir.'/configure/cookie.txt'; 
		if(TRUE) $this->cookie($this->cookies); 
	} 
	
	public function cookie($cookie_file) { 
		if (file_exists($cookie_file)) { 
			$this->cookie_file = $cookie_file; 
		} else { 
			fopen($cookie_file,'w') or $this->error('The cookie file could not be opened. Make sure this directory has the correct permissions'); 
			$this->cookie_file = $cookie_file; 
			fclose($this->cookie_file); 
		}
	} 
	
	public function get($url, $returnfull = false, $headers = 0) { 
		$process = curl_init($url); 
		curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers); 
		curl_setopt($process, CURLOPT_HEADER, $headers); 
		curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent); 
		if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file); 
		if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file); 
		curl_setopt($process,CURLOPT_ENCODING , $this->compression); 
		curl_setopt($process, CURLOPT_TIMEOUT, 30); 
		if ($this->proxy) curl_setopt($process, CURLOPT_PROXY, $this->proxy); 
		curl_setopt($process, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1); 
		$return = curl_exec($process); 
		$info = curl_getinfo($process);
		if($returnfull) return $return;
		curl_close($process); 
		return new curl_response($return, $info); 
	} 
	
	public function post($url,$data, $returnfull = false, $header=0) { 
		if(is_array($data)) {
			$t = ''; $and = false;
			foreach($data as $key=>$value) {
				$t .= ($and ? '&' : '') . urlencode($key) . '=' . urlencode($value);
				if(!$and) $and = true;
			}
			$data = $t;
		}
		//var_dump($data);
		$process = curl_init($url); 
		curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers); 
		curl_setopt($process, CURLOPT_HEADER, 1); 
		curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent); 
		if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file); 
		if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file); 
		curl_setopt($process, CURLOPT_ENCODING , $this->compression); 
		curl_setopt($process, CURLOPT_TIMEOUT, 30); 
		if ($this->proxy) curl_setopt($process, CURLOPT_PROXY, $this->proxy); 
		curl_setopt($process, CURLOPT_POSTFIELDS, $data); 
		curl_setopt($process, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1); 
		curl_setopt($process, CURLOPT_POST, 1); 
		$return = curl_exec($process); 
		$info = curl_getinfo($process);
		if($returnfull) return $return;
		curl_close($process); 
		return new curl_response($return, $info); 
	} 
	
	public function error($error) { 
		return $error; 
	} 
}

class curl_response {
	var $body;
	var $headers;
	var $info;
	
	function __construct($text, $info) {
		$this->info = $info;
		$split1 = strpos($text, "\r\n\r\n");
		$split2 = strpos($text, "\r\r");
		$split3 = strpos($text, "\n\n");
		
		$split = $split1 > 0 						  ? $split1 : 100000000;
		$split = $split2 > 0 && $split2 < $split ? $split2 : $split;
		$split = $split3 > 0 && $split3 < $split ? $split3 : $split;
		
		$splen = $split == $split1 ? 4 : 2;
		
		$this->body = trim(substr($text, $split + $splen));
		$this->headers = substr($text, 0, $split);
	}
	function headers( )
    {
		return $this->http_parse_headers($this->headers);
    }

	function set_cookies($default_domain = false, $force_expire = false) {
		$headers = $this->headers();
		$cookies = $headers['Set-Cookie'];		
		//var_dump($cookies);
		foreach($cookies as $cookie);
		header("Set-Cookie: $cookie");
		/*foreach($cookies as $cookie) {
			$d = array();
			$cf = explode(';', $cookie);
			foreach($cf as $dd) {
				list($v, $vv) = explode('=', $dd);
				$d[$v] = $vv;
			}
			$cs[] = $d;
		}
		
		foreach($cs as $cookie) {
			$name = key($cookie);
			$val = current($cookie);
			//var_dump($force_expire);
			//var_dump($name, $val,$force_expire ?  time()-(60*60*24*7)  : ($cookie['Expires'] ? (strtotime($cookie['Expires']) ? strtotime($cookie['Expires']) : time()-(60*60*24*7) ) : 0), $cookie['Path'], $cookie['Domain'] ? $cookie['Domain'] : $default_domain);
			setrawcookie($name, $force_expire ? '' : $val,$force_expire ?  time()-(60*60*24*7)  : ($cookie['Expires'] ? (strtotime($cookie['Expires']) ? strtotime($cookie['Expires']) : time()-(60*60*24*7) ) : 0), $cookie['Path'], $cookie['Domain'] ? $cookie['Domain'] : $default_domain);
		}*/
		return true;
	}
	/**
	 * Parse through the http headers returned.
	 *
	 * @param string $headers 
	 * @return array
	 * @author David Boskovic
	 */
	function http_parse_headers($headers=false){
		if($headers === false) return false;

		$headers = str_replace("\r","",$headers);
		$headers = explode("\n",$headers);
		foreach($headers as $value){
			$header = explode(": ",$value);
			if($header[0] && $header[1] === NULL){
				$headerdata['status'] = $header[0];
			}
			elseif($header[0] && $header[1] !== NULL){
				if(isset($headerdata[$header[0]]) AND !is_array($headerdata[$header[0]])) {
					$headerdata[$header[0]] = array($headerdata[$header[0]]);
				}
				elseif($headerdata[$header[0]])$headerdata[$header[0]][] = $header[1];
				else
					$headerdata[$header[0]] = $header[1];
			}
		}
		return $headerdata;
	}
}