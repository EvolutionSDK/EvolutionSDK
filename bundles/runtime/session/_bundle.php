<?php

namespace bundles\session;
use Exception;
use bundles\SQL\SQLBundle;
use e;

class Bundle {
	
	private $db_bundle;
	private $dir;
	
	private $_cookie_url = false;
	private $_cookie_name;
	private $_cookie;
	
	private $_key;
	public $_id;
	private $_data = array();
	private $_data_hash;
	private $_flashdata;
	
	private $_session;
	
	private $_robot = false;
	
	private static $_robots = array(
		"Facebook",
		"Accoona-AI-Agent",
		"AOLspider",
		"BlackBerry",
		"bot@bot.bot",
		"CazoodleBot",
		"CFNetwork",
		"ConveraCrawler",
		"Cynthia",
		"Dillo",
		"discoveryengine.com",
		"DoCoMo",
		"ee://aol/http",
		"exactseek.com",
		"fast.no",
		"FAST MetaWeb",
		"FavOrg",
		"FS-Web",
		"Gigabot",
		"GOFORITBOT",
		"gonzo",
		"Googlebot-Image",
		"holmes",
		"HTC_P4350",
		"HTML2JPG Blackbox",
		"http://www.uni-koblenz.de/~flocke/robot-info.txt",
		"iArchitect",
		"ia_archiver",
		"ICCrawler",
		"ichiro",
		"IEAutoDiscovery",
		"ilial",
		"IRLbot",
		"Keywen",
		"kkliihoihn nlkio",
		"larbin",
		"libcurl-agent",
		"libwww-perl",
		"Mediapartners-Google",
		"Metasearch Crawler",
		"Microsoft URL Control",
		"MJ12bot",
		"T-H-U-N-D-E-R-S-T-O-N-E",
		"voodoo-it",
		"www.aramamotorusearchengine.com",
		"archive.org_bot",
		"Teoma",
		"Ask Jeeves",
		"AvantGo",
		"Exabot-Images",
		"Exabot",
		"Google Keyword Tool",
		"Googlebot",
		"heritrix",
		"www.livedir.net",
		"iCab",
		"Interseek",
		"jobs.de",
		"MJ12bot",
		"pmoz.info",
		"SnapPreviewBot",
		"Slurp",
		"Danger hiptop",
		"MQBOT",
		"msnbot-media",
		"msnbot",
		"MSRBOT",
		"NetObjects Fusion",
		"nicebot",
		"nrsbot",
		"Ocelli",
		"Pagebull",
		"PEAR HTTP_Request class",
		"Pluggd/Nutch",
		"psbot",
		"Python-urllib",
		"Regiochannel",
		"SearchEngine",
		"Seekbot",
		"segelsuche.de",
		"Semager",
		"ShopWiki",
		"Snappy",
		"Speedy Spider",
		"sproose",
		"TurnitinBot",
		"Twiceler",
		"VB Project",
		"VisBot",
		"voyager",
		"VWBOT",
		"Wells Search",
		"West Wind",
		"Wget",
		"WWW-Mechanize",
		"www.show-tec.net",
		"xxyyzz",
		"yacybot",
		"Yahoo-MMCrawler",
		"yetibot"
	);
	
	public function __construct($dir) {
		$this->dir = $dir;
	}
	
	/**
	 * Initializes the Session
	 *
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function _on_first_use() {
		
		$this->db_bundle = new SQLBundle($dir);
		
		/**
		 * Grab the cookie name
		 */
		$this->_cookie_name = e::environment()->requireVar('cookie');
		
		/**
		 * Grab the cookie contents and save it to the class
		 */
		$this->_cookie =& $_COOKIE[$this->_cookie_name];
		
		$session = $this->_get_session();
		
		if($this->_robot === true) return;
		
		$this->_key 		= $session->key;
		$this->_id			= $session->id;
		$this->_data		= unserialize(base64_decode($session->data));
		$this->_data_hash	= md5($session->data);
		$this->_flashdata	= isset($this->data['flashdata']) ? $this->data['flashdata'] : array();
		$this->_flashdata['post']	= $_POST;
		$this->_flashdata['get']	= $_GET;
		
		$this->_session =& $session;
	}
	
	/**
	 * Gets the session from the DB
	 *
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	private function _get_session() {
		if(isset($_POST['override_session'])) $this->_cookie = $_POST['override_session'];
		
		if(strlen($this->_cookie) == 32) return $this->_get();
		else return $this->_create();
	}
	
	/**
	 * Gets the Existing Session
	 *
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	private function _get() {
		$session = e::sql()->select('session.list', array('key' => $this->_cookie))->row();
		if(!$session) return $this->_create();
		return $this->db_bundle->getSession($session);
	}
	
	/**
	 * Creates a new session
	 *
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	private function _create() {
		if(array_search($_SERVER['HTTP_USER_AGENT'], self::$_robots) !== false) { $this->_robot = true; return; }
		
		$key = $this->_token(32);
		
		$session = $this->db_bundle->newSession();
		$session->key = $key;
		$session->extra_info = base64_encode(serialize($_SERVER));
		$session->data = base64_encode(serialize(array()));
		$session->ip = $_SERVER['REMOTE_ADDR'];
		$session->save();
		
		setcookie($this->_cookie_name, $key, 0, '/', ($this->_cookie_url ? $this->_cookie_url : false), false);
		
		return $session;
	}
	
	/**
	 * Add a flashdata variable
	 *
	 * @param string $key 
	 * @param string $subkey 
	 * @param string $value 
	 * @return void
	 * @author David Boskovic
	 */
	public function flashdata_push($key, $subkey, $value) {
		$this->data['flashdata'][$key][$subkey][] = $value;
	}
	
	/**
	 * Add a message to the flashdata. This is weird too.
	 *
	 * @param string $type 
	 * @param string $message 
	 * @return void
	 * @author David Boskovic
	 */
	public function message($type, $message) {
		return $this->flashdata_push('result_data', 'messages', array('type' => $type, 'message' => $message));
	}
	
	/**
	 * Adds and returns flashdata
	 *
	 * @param string $key 
	 * @param string $value 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function flashdata($key, $value = false) {
		
		if($value !== false) {
			if(is_array($this->data['flashdata'][$key]) && $key == 'result_data') foreach($value['messages'] as $msg) 
				$this->flashdata_push($key, 'messages', $msg);
			
			else $this->data['flashdata'][$key] = $value;
			
			return true;
		}
		
		else {
			if(isset($this->data['flashdata'][$key])) 
				unset($this->data['flashdata'][$key]);

			return $this->flashdata[$key];
		}
		
	}
	
	/**
	 * Saves the updated session
	 *
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function save() {
		if(!($this->_session instanceof \Evolution\SQL\Model))
			throw new \Exception("Session has not be instantiated, cannot save.");
		
		$serialize = base64_encode(serialize($this->_data));
		$session =& $this->_session;
		
		if(md5($serialize) !== $this->_data_hash)
			$session->data = $serialize;
		
		$session->hits++;
		
		$session->save();
	}
	
	/**
	 * Saving/retrieving data
	 *
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function data() {
		$args = func_get_args();
				
		/**
		 * Grab the method to perform
		 */
		$method = array_shift($args);
		
		switch($method) {
			case 'get':
				/**
				 * Grab the data from the session
				 */
				$data = $this->_data;

				/**
				 * Move down the array based on the args passed
				 */
				foreach($args as $arg) $data = $data[$arg];

				/**
				 * Finally return the data
				 */
				return $data;
			break;
			case 'set':
				\Evolution\Kernel\Trace::add(__NAMESPACE__, "hit in session");
			
				/**
				 * Grab the end of the arguments (Data)
				 */
				$data = array_pop($args);
				
				/**
				 * Reverse the array so everything lines up right (IMPORTANT)
				 */
				$args = array_reverse($args);
				
				/**
				 * Loop through the array making our subarrays
				 */
				foreach($args as $arg) $data = array($arg => $data);
				
				/**
				 * Merge our array into the session
				 */
				$this->_data = array_replace_recursive((is_array($this->_data) ? $this->_data : array()), $data);
				
				return $this;
			break;
			case 'unset':
				\Evolution\Kernel\Trace::add(__NAMESPACE__, "hit in session");
							
				unset($this->_data[$args[0]][$args[1]]);
				
				return $this;
			break;
			default:
				throw new \Exception("You cannot call `e::session()->data()` with out providing `get` or `set` as the first arguement");
			break;
		}
		
	}
	
	/**
	 * Generate a random session ID.
	 *
	 * @param string $len 
	 * @param string $md5 
	 * @return void
	 * @author Andrew Johnson
	 * @website http://www.itnewb.com/v/Generating-Session-IDs-and-Random-Passwords-with-PHP
	 */
	private function _token( $len = 32, $md5 = true ) {

	    # Seed random number generator
	    # Only needed for PHP versions prior to 4.2
	    mt_srand( (double)microtime()*1000000 );

	    # Array of characters, adjust as desired
	    $chars = array(
	        'Q', '@', '8', 'y', '%', '^', '5', 'Z', '(', 'G', '_', 'O', '`',
	        'S', '-', 'N', '<', 'D', '{', '}', '[', ']', 'h', ';', 'W', '.',
	        '/', '|', ':', '1', 'E', 'L', '4', '&', '6', '7', '#', '9', 'a',
	        'A', 'b', 'B', '~', 'C', 'd', '>', 'e', '2', 'f', 'P', 'g', ')',
	        '?', 'H', 'i', 'X', 'U', 'J', 'k', 'r', 'l', '3', 't', 'M', 'n',
	        '=', 'o', '+', 'p', 'F', 'q', '!', 'K', 'R', 's', 'c', 'm', 'T',
	        'v', 'j', 'u', 'V', 'w', ',', 'x', 'I', '$', 'Y', 'z', '*'
	    );

	    # Array indice friendly number of chars; empty token string
	    $numChars = count($chars) - 1; $token = '';

	    # Create random token at the specified length
	    for ( $i=0; $i<$len; $i++ )
	        $token .= $chars[ mt_rand(0, $numChars) ];

	    # Should token be run through md5?
	    if ( $md5 ) {

	        # Number of 32 char chunks
	        $chunks = ceil( strlen($token) / 32 ); $md5token = '';

	        # Run each chunk through md5
	        for ( $i=1; $i<=$chunks; $i++ )
	            $md5token .= md5( substr($token, $i * 32 - 32, 32) );

	        # Trim the token
	        $token = substr($md5token, 0, $len);

	    } return $token;
	}
	
	public function __call($func, $args) {
		if(!is_object($this->_session))
			throw new Exception("Trying to load a session instance before initialized");
		return call_user_func_array(array($this->_session, $func), $args);
	}
	
}