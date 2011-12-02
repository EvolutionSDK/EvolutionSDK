<?php

namespace Bundles\Action;
use Exception;
use e;

ini_set('max_file_uploads', 20);

class ActionException extends Exception { }

class Action {
	
	public $data = array();
	
	public $save_data = false;
	public $raw = false;
	public $input_data = array();
	
	protected $_has_checked = array();
	
	/**
	 * Response type. HTTP will redirect. JSON and XML will return their respective responses.
	 *
	 * @var string
	 */
	protected $type = 'http'; // http, return, json
	
	/**
	 * Default success message.
	 *
	 * @var string
	 */
	protected $success_message = 'Success!';
	
	/**
	 * An array of messages generated by the validation methods.
	 *
	 * @var string
	 */
	protected $messages = array();
	
	public function __construct($data = false) {
		$this->_inject_data(e::session()->data('get','_actions', get_class($this)));
		
		
		if(isset($_REQUEST['_method'])) {
			/**
			 * Load the data from the session, and $_POST, $_GET variables
			 */
			$this->_load_data($_POST, $_GET);
			
			/**
			 * Set the Success and Failure URL's Based on if they are in the data or not
			 */
			if(isset($this->data['_success_url'])) $this->_redirect_success_url = $this->data['_success_url'];
			if(isset($this->data['_failure_url'])) $this->_redirect_success_url = $this->data['_failure_url'];

			/**
			 * Grab the method to call
			 */
			$method = $_REQUEST['_method'];
			$this->$method();
			
			/**
			 * Close the action
			 */
			return $this->_close();
		}
		
		/**
		 * Start by settign save data to true
		 */
		$this->save_data = true;
		
		/**
		 * Reset the currently stored data if it is requested and allowed
		 */
		if(!isset($this->_allow_reset) || $this->_allow_reset) {
			if(isset($_REQUEST['_reset']) || isset($this->data['_reset']))
				$this->reset();
		}
		
		/**
		 * Load the data from where ever it is preset/requested
		 */
		if(!$data) $this->_load_data($_POST, $_GET);
		else if(is_array($data)) {
			$this->input_data = $data;
			$this->type = false;
			$this->_load_data($data);
		}
		else $this->_load_data();
		
		/**
		 * Load File Data
		 */
		if(!empty($_FILES)) {
			$files = $_FILES;
			
			// Handle Single File
			// @todo
			
			// Handle Multiple Files
			$out = array();
			foreach($files as $key => $data) {
				foreach($data as $key1 => $data1) {
					foreach($data1 as $key2 => $data2) {
						$out[$key]['files'][$key2][$key1] = $data2;
					}
				}
			}
			
			$this->_load_data($out);
		}
		
		/**
		 * Unset the reset variable
		 */
		unset($this->data['_reset']);
		
		/**
		 * Set the Success and Failure URL's Based on if they are in the data or not
		 */
		if(isset($this->data['_success_url'])) $this->_redirect_success_url = $this->data['_success_url'];
		if(isset($this->data['_failure_url'])) $this->_redirect_failure_url = $this->data['_failure_url'];
		
		if(isset($this->data['_action_type'])) $this->type = $this->data['_action_type'];
		if(isset($this->data['_has_checked'])) $this->_has_checked = $this->data['_has_checked'];

		if($data === true) $this->type = false;

		/**
		 * if the success or failure URL's are referencing the last page find what the
		 * last page was and set it to the respective variables
		 */
		if(isset($this->data['_success_url']) && $this->data['_success_url'] == '@last_page') {
			if(e::session()->data('get','_last_page')) $this->_redirect_success_url = e::session()->data('get','_last_page');
			else if(isset($_SERVER['HTTP_REFERER'])) $this->_redirect_success_url = $_SERVER['HTTP_REFERER'];
		}
		if(isset($this->data['_failure_url']) && $this->data['_failure_url'] == '@last_page') {
			if(e::session()->data('get','_last_page')) $this->_redirect_failure_url = e::session()->data('get','_last_page');
			else if(isset($_SERVER['HTTP_REFERER'])) $this->_redirect_failure_url = $_SERVER['HTTP_REFERER'];
		}
		
		$this->_load_files();
		
		/**
		 * Run the initialization function on the action
		 */
		$this->init();
		
		/**
		 * Validate the validation functions
		 */
		$this->check_formats();
		
		/**
		 * Make sure that we have some required variables and methods in place
		 */
		$this->_dependency_checks();
		
		/**
		 * How did the validation go
		 */
		$results = $this->results();
		
		/**
		 * If things validated properly and we need to complete the action go ahead and compelte it
		 */
		if(isset($results['success']) && $results['success'] && isset($this->data['_complete']) && $this->data['_complete'] > 0 )
			{ $this->complete(); $this->reset(); }
		
		/**
		 * Remove the completion from the action
		 */	
		unset($this->data['_complete']);
		unset($this->data['_reset']);
		
		/**
		 * Save Messages
		 */
		e::session()->flashdata('result_data', $this->results());
		
		/**
		 * Lets destruct our action
		 */
		$this->_close();
	}
	
	protected function _dependency_checks() {
		if(strlen($this->_redirect_success_url) == 0)
			throw new ActionException('You must have a value in `$this->_redirect_success_url` in the action `'.get_class($this).'`.');
		if(strlen($this->_redirect_failure_url) == 0)
			throw new ActionException('You must have a value in `$this->_redirect_failure_url` in the action `'.get_class($this).'`.');
	}
	
	/**
	 * Finish up the action
	 *
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	protected function _close() {
		$this->data['_has_checked'] = $this->_has_checked;
		
		/**
		 * Save the data to the session
		 */
		$data = $this->data;
		
		if($this->save_data) 
			e::session()->data('set', '_actions', get_class($this), $data)->save();
		
		/**
		 * If the output is raw stop
		 */
		if($this->raw) { e\Complete(); exit; }
		/**
		 * Redirect to respective URL's
		 */
		if($this->type == 'http') {
			$results = $this->results();
			
			/**
			 * @todo: Determine if this should even be here
			 */
			if(isset($this->data['_url'])) e\redirect($this->data['_url']);
			
			if(isset($results['success']) && $results['success'])
				e\redirect($this->_redirect_success_url);
			if(isset($results['success']) && !$results['success'])
				e\redirect($this->_redirect_failure_url);
		}
		
		/**
		 * Return JSON of the output
		 */
		else if($this->type == 'json') {
			e\disable_trace();
			header("Content-Type: application/json");
			echo json_encode($this->results());
		}
		else if($this->type == 'php') {
		}
		
		/**
		 * @author: Kelly Lauren Summer Becker has deprecated the XML output.
		 */

	}
	
	/**
	 * Get results, and messages
	 *
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	protected function results() {
		$success = true;
		
		foreach($this->messages as $key=>$result) {
			if($result['type'] === 'error') $success = false;
		}
		
		return array('success' => $success, 'messages' => $this->messages, 'data' => $this->data);
	}
	
	/**
	 * Add a message to the messages array
	 *
	 * @param string $type 
	 * @param string $msg 
	 * @param string $field 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	protected function _message($type, $msg, $field = false) {
		$this->messages[] = array('field' => $field ? $field : '@', 'type' => $type, 'message' => $msg);
		return $type == 'error' ? false : true;
	}
	
	/**
	 * Placeholder for extended init function
	 *
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	protected function init() {
		
	}
	
	/**
	 * Check the submitted data
	 *
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	protected function check_formats() {
		if(method_exists($this, '_extend')) $this->_extend();
		
		foreach($this->data as $key => $value) {
			if(isset($this->_has_checked[$key]) && $this->_has_checked[$key] === 1)
				continue;
			
			if(method_exists($this, '_validate_'.$key))
				$result = call_user_func(array($this, '_validate_'.$key), $value);
			else $result = true;
			
			if($result === true || is_null($result)) {
				$result = $this->results();
				$result = $result['success'];
			}
			
			if($result === true) $this->_has_checked[$key] = 1;
			else $this->_has_checked[$key] = 0;
		}
		
		if(method_exists($this, '_validate_all')) $this->_validate_all();
	}
	
	public function success() {
		$result = $this->results();
		return $result['success'] ? true : false;
	}
	
	/**
	 * Merge all the data together
	 *
	 * @param string $var 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	protected function _load_data() {
		$args = func_get_args();
		foreach($args as $pd) {
			// unset the has_checked of this item
			foreach($pd as $k => $d) {
				if(isset($this->_has_checked[$k]) || isset($this->data['_has_checked'][$k])) {
					unset($this->_has_checked[$k]);
					unset($this->data['_has_checked'][$k]);
				}
			}
			if(!is_array($pd) || count($pd) == 0) continue;
			$this->data = e\array_merge_recursive_simple($this->data, $pd);
		}
		return true;
	}
	
	/**
	 * This injects data into the action without overriding the has_checked list.
	 *
	 * @return void
	 * @author David Boskovic
	 */
	protected function _inject_data() {
		$args = func_get_args();
		foreach($args as $pd) {
			if(!is_array($pd) || count($pd) == 0) continue;
			$this->data = e\array_merge_recursive_simple($this->data, $pd);
		}
		return true;
	}
	
	protected function _load_files() {
		$pd = $_FILES;
		
		if(!is_array($pd) || count($pd) == 0) return false;
	}
	
	public function reset() {
		$this->data = array();
		e::session()->data('unset', '_actions', get_class($this));
		e::session()->save();
	}
	
	/**
	 * Escape a string, or multidimensional array
	 *
	 * @param string $var 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function _kescape($var, $extra = array('html' => 0, 'url' => 0)) {
		if(!$var) return false;
		
		if(is_array($var)){
			foreach($var as $key=>$val) $return[$key] = $this->_kescape($val, $extra);
			return $return;
		}
		
		if($extra['html']&&$extra['url'])
			return $this->_kescape_string(urldecode(htmlentities($var)));
		elseif($extra['html']&&!$extra['url'])
			return $this->_kescape_string(htmlentities($var));
		elseif(!$extra['html']&&$extra['url'])
			return $this->_kescape_string(urldecode($var));
		elseif(!$extra['html']&&!$extra['url'])
			return $this->_kescape_string($var);
		else return false;
	}
	
	public function _kescape_string($str) 
	{ 
	   $len=strlen($str); 
	    $escapeCount=0; 
	    $targetString=''; 
	    for($offset=0;$offset<$len;$offset++) { 
	        switch($c=$str{$offset}) { 
	            case "'": 
	                    if($escapeCount % 2 == 0) $targetString.="\\"; 
	                    $escapeCount=0; 
	                    $targetString.=$c; 
	                    break; 
	            case '"': 
	                    if($escapeCount % 2 == 0) $targetString.="\\"; 
	                    $escapeCount=0; 
	                    $targetString.=$c; 
	                    break; 
	            case '\\': 
	                    $escapeCount++; 
	                    $targetString.=$c; 
	                    break; 
	            default: 
	                    $escapeCount=0; 
	                    $targetString.=$c; 
	        } 
	    } 
	    return $targetString; 
	}
	
}