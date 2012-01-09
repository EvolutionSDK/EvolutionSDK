<?php

namespace Bundles\LHTML;
use Exception;

class Node_SSL extends Node {
	
	public function init() {
		$this->element = false;
		
		if(isset($this->attributes['domain']))
			$domain = $this->attributes['domain'];
		else
			$domain = $_SERVER['HTTP_HOST'];
		
		if(array_pop(explode('.', $domain)) == 'dev') return;
		
		if ($_SERVER['HTTPS'] != "on") { 
		    $url = "https://". $domain . $_SERVER['REQUEST_URI']; 
			header("Location: $url");
		    exit; 
		}
	}
	
}