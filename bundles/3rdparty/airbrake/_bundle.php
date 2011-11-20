<?php

namespace Bundles\AirBrake;
use Exception;
use Airbrake;
use e;

class Bundle {
	
	private static $_in_airbrake = false;
	
	public function _on_exception(Exception $exception) {
		
		if(self::$_in_airbrake == true)
			return;
			
		self::$_in_airbrake = true;
		
		$enabled = e::environment()->requireVar('Airbrake.Enabled', 'yes | no');
		if($enabled !== true || $enabled === 'yes')
			return;

		require_once __DIR__ . '/php-airbrake/src/Airbrake/Client.php';
		require_once __DIR__ . '/php-airbrake/src/Airbrake/Configuration.php';
		
		e::environment()->_reset_exception_status();
		$apiKey  = e::environment()->requireVar('Airbrake.APIKey'); // This is required

		$options = array(); // This is optional

		$options['environmentName'] = gethostname();

		$config = new Airbrake\Configuration($apiKey, $options);
		$client = new Airbrake\Client($config);
		
		$client->notifyOnException($exception);
	}
	
}