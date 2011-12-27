<?php

namespace Controller;
use Exception;
use e;

/**
 * FormController
 */
abstract class FormController {
	
	private $class;
	
	protected $data = null;
	
	protected $input = null;
	
	/**
	 * Load stored session data for this controller then checks
	 * For an overriding init function
	 *
	 * @author Kelly Lauren Summer Becker
	 */
	final public function __construct() {
		$this->input =& $_REQUEST;
		$this->class = get_class($this);
		if(isset(e::$session->data->FormController[$this->class])) 
			$this->data = e::$session->data->FormController[$this->class];
		
		/**
		 * Run init every page load
		 */
		$this->init();
	}
	
	/**
	 * Saves $this->data to the session for later
	 *
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	final protected function __saveData() {
		e::$session->data->FormController[$this->class] = $this->data;
		e::$session->save();
	}
	
	/**
	 * Set Success and Failure URLS
	 *
	 * @param string $data 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	final protected function __getUrls(&$data = null) {
		if(!is_array($data)) return;
		
		if(isset($this->input['_success_url']) && !isset($data['_success_url']))
			$data['_success_url'] = $this->input['_success_url'];
		if(isset($this->input['_failure_url']) && !isset($data['_failure_url']))
			$data['_failure_url'] = $this->input['_failure_url'];
	}
	
	/**
	 * Gets $_REQUEST Data and merges is with existing data if passed
	 *
	 * @param string $key 
	 * @param string $data 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	final protected function __getInput($key = null, $data = array()) {
		if(is_null($key)||empty($key)||!isset($this->input[$key])) 
			$data = e\array_merge_recursive_simple($this->input, $data);
		else
			$data = e\array_merge_recursive_simple($this->input[$key], $data);
		
		$this->__getUrls($data);
		
		return $data;
	}
	
	/**
	 * Clears $this->data for the entire controller
	 *
	 * @param string $run 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	final protected function __clearData() {
		e::$session->data->FormController[$this->class] = null;
		e::$session->save();
		$this->data = null;
	}
	
	protected function init() {}
}