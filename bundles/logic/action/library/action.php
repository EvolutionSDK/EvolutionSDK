<?php

namespace Bundles\Action;
use Exception;
use e;

ini_set('max_file_uploads', 20);

class ActionException extends Exception { }

abstract class Action {
	
	public $data = array();
	protected $save = false;
	protected $class = false;
	protected $success = true;
	protected $complete = false;
	protected $type = 'redirect';
	
	protected $_success_url = false;
	protected $_failure_url = false;
	
	private $messages = array();
	
	final public function __construct($data = array(), $juststop = false) {
		$this->class = get_class($this);
		
		if(isset($this->data['_reset']))
			$this->_injectData(e::$input->all);
		else {
			$session = e::$session->data('get', 'Action');
			$this->_injectData($data, $session[$this->class], e::$input->all);
		}
		
		if($juststop) return;
		
		if(isset($this->data['_success_url']))
			$this->_success_url = $this->data['_success_url'];
		if(isset($this->data['_failure_url']))
			$this->_failure_url = $this->data['_failure_url'];
		if(isset($this->data['_type']))
			$this->type = $this->data['_type'];
		if(isset($this->data['_complete']))
			$this->complete = true;
		
		$this->init();
		
		foreach($this->data as $key=>$validate) {
			if(!is_array($validate)) continue;
			if(method_exists($this, $method = '_validate_'.$key)) {
				if($this->$method($validate) === false)
					$this->success = false;
			}
		}
		
		if($this->success && $this->complete)
			$this->complete();
			
		if($this->save)
			$this->_saveData();
			
		$this->_broadcastMessages($this->class);
		
		if($this->type == 'redirect') {
			e\Redirect($this->success ? $this->_success_url : $this->_failure_url);
			return;
		}
		
		if($this->type == 'json') {
			header('Content-Type: application/json');
			echo json_encode(array(
				'messages' => $this->messages,
				'class' => $this->class
			));
			
			return;
		}
		
		return;
	}
	
	protected function init() {}
	
	/**
	 * Data Functions
	 */
	
	final protected function _saveData() {
		e::$session->data('set', 'Action', array($this->class => $this->data));
		e::$session->save();
	}
	
	final protected function _clearData() {
		e::$session->data('set', 'Action', array($this->class => null));
		e::$session->save();
		$this->data = null;
	}
	
	final protected function _injectData() {
		$data = $this->data;
		
		foreach(func_get_args() as $source) {
			if(!is_array($source)) $source = array();
			$data = e\array_merge_recursive_simple($data, $source);
		}
			
		return $this->data = $data;
	}
	
	/**
	 * Messages
	 */
	
	final protected function _getMessages() {
		$fields = array();
		$messages_array = array();
		foreach($this->messages as $message) {
			if(empty($message['field']))
				$message['field'] = '--global';
			if(!is_array($fields[$message['field']]))
				$fields[$message['field']] = array();
			if(!is_array($fields[$message['field']][$message['type']]))
				$fields[$message['field']][$message['type']] = array();
			
			$fields[$message['field']][$message['type']][] = $message;
		}
		foreach($fields as $field => $types) {
			
			foreach($types as $type => $messages) {
				
				$output = '';
				$first = true;
				
				foreach($messages as $message) {
					$output .= str_replace('%field', ($first ? '<span class="field">' . $message['name'] . '</span>' : ' and'), $message['message']);
					$first = false;
				}
			
				$messages_array[] = array('type' => $type, 'field' => $field, 'message' => $output);
			}
		}
		return $messages_array;
	}
	
	final protected function _hasMessages() {
		return count($this->messages) > 0;
	}
	
	final protected function _broadcastMessages($namespace = 'global') {
		foreach($this->_getMessages() as $message)
			e::$events->message($message, $namespace);
		$this->messages = array();
	}
	
	final public function _message($type, $message, $field = null, $name = null) {
		if($type == 'error') $this->success = false;
		$this->messages[] = array('type' => $type, 'message' => $message, 'field' => $field, 'name' => $name);
	}
	
	/**
	 * Content Filtering
	 */
	
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