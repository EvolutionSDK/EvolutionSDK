<?php

namespace Controller;
use Exception;
use e;

/**
 * FormController
 */
abstract class FormController {
	
	protected $data;
	
	protected $_complete = true;
	
	final public function __construct() {
		$this->data = e::validator($_POST);
		$class = get_class($this);
		$this->data->mergeExistingData(e::$session->data->$class);
		
		/**
		 * Run init every page load
		 */
		$this->init();
	}
	
	final public function _complete() {
		
		$class = get_class($this);
		e::$session->data->$class = $this->data->clean();
		
		if(!$this->_complete)
			return;
		
		if($this->data->hasMessages()) {
			$this->data->broadcastMessages(get_class($this));
			$which = '_failure_url';
		} else {
			/**
			 * No errors
			 */
			$which = '_success_url';
		}
		
		$to = $this->data->$which->clean();
		if(!$to)
			throw new Exception("No `$which` in form controller `$class` data");
		e\redirect($to);
	}
	
	final protected function _clear() {
		$this->data = e::validator(null);
	}
	
	protected function init() {}
}