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
	
	/**
	 * Load stored session data for this controller then checks
	 * For an overriding init function
	 *
	 * @author Kelly Lauren Summer Becker
	 */
	final public function __construct() {
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