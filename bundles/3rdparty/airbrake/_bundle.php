<?php

namespace Bundles\AirBrake;
use Exception;
use Airbrake;
use e;

class Bundle {
	
	private static $enabled;
	private static $apiKey;
	private static $options;
	private static $config;
	private static $client;
	
	public function __construct() {
	
		$lib = __DIR__ . '/php-airbrake';

		if(!is_dir($lib))
			throw new Exception("PHP Airbrake library is not installed, run command `sudo git clone git://github.com/nodrew/php-airbrake.git $lib`");
	}
	
	public function _on_framework_loaded() {
		
		self::$enabled = e::environment()->requireVar('Airbrake.Enabled', 'yes | no');
		
		require_once __DIR__ . '/php-airbrake/src/Airbrake/Client.php';
		require_once __DIR__ . '/php-airbrake/src/Airbrake/Configuration.php';
		
		self::$apiKey  = e::environment()->requireVar('Airbrake.APIKey'); // This is required
		
		self::$options = array(); // This is optional
		
		self::$options['environmentName'] = e::environment()->requireVar('EnvironmentName', 'Name your individual computer / environment');
		
		self::$config = new Airbrake\Configuration(self::$apiKey, self::$options);
		self::$client = new Airbrake\Client(self::$config);
	}
	
	public function _on_exception(Exception $exception) {
		self::$client->notifyOnException($exception);
	}
	
}