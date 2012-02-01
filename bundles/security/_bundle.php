<?php

namespace Bundles\Security;
use Exception;
use e;

/**
 * Security Bundle
 * Manage super admin access and authorization logs
 * @author Nate Ferrero
 */
class Bundle {

	private $developer = false;
	
	public function _on_framework_security() {
		
		// Add manager
		e::configure('manage')->activeAddKey('bundle', __NAMESPACE__, 'security');

		$developer = false;

		// Check cookie login
		if(isset($_COOKIE['e-developer'])) {
			
			// Check cookie data
			$cookie = explode('_', $_COOKIE['e-developer'], 2);
			$name = $cookie[0];

			// Use key to encrypt cookie
			$keys = e::configure('developers');
			$key = $keys->$name;

			$check = $this->genCookieSegment($name, $key);

			if($check === $cookie[1])
				$developer = true;
		}

		// Check credentials if not logged in
		if(!$developer) {
			$key = $this->getPOSTKey();
			if(!is_null($key)) {

				// Get the account name
				$name = $key[0];

				// Load the encoded version of the key
				$key = $this->validateCredentials($key);

				// Compare it to existing keys
				$keys = e::configure('developers');
				if($keys->$name === $key) {

					setcookie('e-developer', $name . '_' . $this->genCookieSegment($name, $key));

					$developer = true;
				}
			}
		}

		$this->developer = $developer;
	}

	/**
	 * Return developer status
	 */
	public function isDeveloper() {
		return $this->developer;
	}

	/**
	 * Security access for development
	 */
	public function developerAccess() {

		// If cookie and post credentials both failed
		if(!$this->developer)
			$this->page('access');

	}

	public function genCookieSegment($name, $key) {
		$stuff = md5($_SERVER['REMOTE_ADDR'].'_'.date('m'));
		return md5($key . $stuff . $name);
	}

	public function getPOSTKey() {
		return isset($_POST['e-developer-credentials']) ? explode('.', $_POST['e-developer-credentials'], 2) : null;
	}

	public function route() {
		$key = $this->getPOSTKey();
		if(!is_null($key)) {
			$name = $key[0];
			if(!isset($key[1]) || strlen($key[1]) < 4)
				$extra = 'Please enter a longer passphrase in the format <i>name.passphrase</i>';
			else {
				$key = $this->validateCredentials($key);
				$extra = 'Generated Key: ' . $key;
			}
		} else {
			$extra = '';
		}

		$this->page('generate', $extra);
	}

	public function validateCredentials($key) {
		if(!is_array($key) || !isset($key[1]))
			return false;
		list($name, $pass) = $key;
		return($name . '_' . md5(base64_encode(md5($key . '!@#$%^*()' . $pass))));
	}

	public function page($which, $extra = '') {
		
		
		/**
		 * Show developer login form
		 */
		$title = "EvolutionSDK&trade; Developer Access";
		$css = file_get_contents(__DIR__.'/../debug/theme.css') . self::style();
		$header = "<span>$title</span>";
		
		echo "<!doctype html><html><head><title>$title</title><style>$css</style></head><body class='_e_dump'><div class='manage-page'>";

		echo $this->developerAccessForm($which, $extra);
		
		echo "</div></body></html>";

		// Exit PHP
		exit;
	}

	public function developerAccessForm($which = 'access', $extra = '') {
		if(strlen($extra) > 0)
			$extra = "<div class='message'>$extra</div>";

		if($which == 'access') {
			$message = 'Enter Credentials';
			$form = "<form method='post'><input name='e-developer-credentials' type='password' /></form>";
		} elseif($which == 'generate') {
			$message = 'Generate Security Credentials';
			$form = "<form method='post'>$extra<input name='e-developer-credentials' type='password' /></form>";
		}
		return "<div class='section'><h2>$header</h2><h4>$message</h4><div class='trace'>$form<div style='clear: both'></div></div></div>";
	}

	private static function style() {
		return <<<_
body._e_dump {
	margin: 2em auto;
	width: 600px;
	h1 {text-align: center;}
}
form {
	padding: 1em;

}
input {
	box-sizing: border-box;
	width: 100%;
	line-height: 1.6;
	padding: 8px;
	box-shadow: inset 0 1px 5px #aaa;
	border: 1px solid #aaa;
	border-radius: 2px;
	text-align: center;
}
.message {
	margin-bottom: 1em;
	font-family: Monaco, monospace;
	font-size: 12px;
}
_;
	}
}