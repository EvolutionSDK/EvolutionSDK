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
		
		/**
		 * Save the data to the session
		 */
		e::$session->data->$class = $this->data->clean();
		
		/**
		 * If we are not completing then return
		 */
		if(!$this->_complete)
			return;
		
		/**
		 * Check if the validation returned any errors
		 * @author: Kelly Lauren Summer Becker
		 */
		$which = $this->data->isSuccess() ? '_success_url' : '_failure_url';
		
		/**
		 * Broadcast all messages both Success, and Errors
		 * @author: Kelly Lauren Summer Becker
		 */
		$this->data->broadcastMessages(get_class($this));
		
		$to = $this->data->$which->clean();
		if(!$to)
			throw new Exception("No `$which` in form controller `$class` data");
			
		$this->_clear('run');
		
		e\redirect($to);
	}
	
	final protected function _clear($run = false) {
		if(!$run) return false;
		
		switch($run) {
			case 'quene':
				static $clear = true;
			break;
			case 'unquene':
				if(isset($clear))
					$clear = false;
			break;
			case 'run':
				if(isset($clear) && $clear == true)
					$this->data = e::validator(null);
			break;
		}
			
		return true;
	}
	
	protected function init() {}
}