<?php

namespace Bundles\Validator;
use Exception;
use e;

/**
 * Validator Bundle
 */
class Bundle {

	public function __callBundle() {
		return new Collection(func_get_args());
	}
	
	/**
	 * Set the human readable name
	 */
	public function _on_validate_humanReadableName($field, $value, $name) {
		$field->setHumanReadableName($name);
	}
	
	/**
	 * Basic validation functions
	 */
	public function _on_validate_required($field, $value, $length) {
		if(empty($value))
			$field->error('%field is required');
	}
	
	public function _on_validate_minLength($field, $value, $length) {
		if(strlen($value) < $length)
			$field->error('%field must be at least ' . $length . ' characters');
	}
	
	public function _on_validate_maxLength($field, $value, $length) {
		if(strlen($value) > $length) {
			$field->error('%field must not be longer than ' . $length . ' characters');
			$field->cleanValue(substr($value, 0, $length));
		}
	}
	
	public function _on_validate_number($field, $value) {
		if(!is_numeric($value)) {
			$field->error('%field must be a number');
			$field->cleanValue((float) $value);
		}
	}
	
	public function _on_validate_precision($field, $value, $precision) {
		$field->cleanValue(round($value, $precision));
	}
	
	public function _on_validate_minValue($field, $value, $ref) {
		if($value < $ref) {
			$field->error('%field must not be less than ' . $ref);
			$field->cleanValue($ref);
		}
	}
	
	public function _on_validate_maxValue($field, $value, $ref) {
		if($value > $ref) {
			$field->error('%field must not be greater than ' . $ref);
			$field->setCleanValue($ref);
		}
	}
}

/**
 * Setup field class
 */
class Collection {
	
	public $data = array();
	
	private $messages = array();
	
	public $fields = array();
	
	private $success = true;
	
	public function isSuccess() {
		return $this->success;
	}
	
	public function getMessages() {
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
	
	public function hasMessages() {
		return count($this->messages) > 0;
	}
	
	public function broadcastMessages($namespace = 'global') {
		foreach($this->getMessages() as $message)
			e::$events->message($message, $namespace);
		$this->messages = array();
	}
	
	public function mergeExistingData($data) {
		if($data instanceof Collection) {
			$data = $data->raw();
		}
		if(is_array($data)) {
			foreach($data as $key => $value) {
				if(!isset($this->data[$key]))
					$this->data[$key] = $value;
			}
		}
	}
	
	/**
	 * Load data sources
	 */
	public function __construct($sources) {
		foreach($sources as $data) {
			
			/**
			 * Add source elements to data
			 */
			if(is_array($data)) {
				foreach($this->_formatData($data) as $key => $value) {
					$this->data[$key] = $value;
				}
			}
		}
	}
	
	/**
	 * Rearranges array to a non multidimensional array
	 *
	 * @param string $data 
	 * @param string $stack 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	private function _formatData($data = array(), $stack = false) {
		$prefix = !$stack ? '' : $stack.'->';
		
		$return = array();
		foreach($data as $key=>$val) {
			if(is_array($val)) 
				$return = array_merge($return, $this->_formatData($val, $prefix.$key));
			else
				$return = array_merge($return, array($prefix.$key => $val));
		}
		
		return $return;
	}
	
	/**
	 * Set a default value if not set
	 */
	public function setDefault($var, $value) {
		if(!isset($this->data[$var]))
			$this->data[$var] = $value;
	}
	
	/**
	 * Validate a particular field
	 */
	public function __get($field) {
		if(!isset($this->fields[$field]))
			$this->fields[$field] = new Field($this, $field, isset($this->data[$field]) ? $this->data[$field] : null);
		return $this->fields[$field];
	}
	
	public function raw() {
		return $this->data;
	}
	
	/**
	 * Return cleaned data
	 */
	public function clean() {
		$clean = $this->data;
		foreach($this->fields as $field)
			$clean[$field->getField()] = $field->clean();
		return $clean;
	}
	
	public function addMessage($type, $message, $field = null, $name = null) {
		if($type == 'error') $this->success = false;
		$this->messages[] = array('type' => $type, 'message' => $message, 'field' => $field, 'name' => $name);
	}
}

/**
 * Single value validation
 */
class Field {
	
	private $value;
	
	private $clean;
	
	private $field;
	
	private $parent;
	
	private $collection;
	
	private $humanReadableName;
	
	public function __construct($collection, $field, $value, $parent = false) {
		$this->parent = !$parent ? $field : $parent;
		$this->collection = $collection;
		$this->field = $field;
		$this->humanReadableName = ucwords(str_replace(array('-', '_', '.'), array(' ', ' ', ' '), $field));
		$this->value = $value;
		$this->clean = $value;
	}
	
	public function raw() {
		$children = $this->children();
		if(empty($children)) return $this->value;
		
		$return = array();
		foreach($children as $key=>$val)
			$return[$key] = $val->raw();
		
		return $return;
	}
	
	public function getField() {
		return $this->field;
	}
	
	public function clean() {
		$children = $this->children();
		if(empty($children)) return $this->clean;
		
		$return = array();
		foreach($children as $key=>$val)
			$return[$key] = $val->clean();
		
		return $return;
	}
	
	/**
	 * Finds all child variables of this particular object
	 *
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function children() {
		$parent = $this->parent;
		
		$children = array_filter(array_flip($this->collection->data), function($key) use ($parent) {
			if(substr($key, 0, strlen($parent) + 2) !== $parent.'->')
				return false;
			else
				return true;
		});
		$return = array();
		foreach($children as $key) {
			$key = substr($key, strlen($parent) + 2);
			$return[$key] = $this->$key; 
		}
		return $return;
	}
	
	public function setCleanValue($value) {
		$this->clean = $value;
	}
	
	public function error($message) {
		$this->collection->addMessage('error', $message, $this->field, $this->humanReadableName);
	}
	
	public function setHumanReadableName($name) {
		$this->humanReadableName = $name;
	}
	
	/**
	 * Validate a sub field
	 * @author: Kelly Lauren Summer Becker
	 */
	public function __get($field) {
		$field = $this->parent.'->'.$field;
		
		if(!isset($this->collection->fields[$field]))
			$this->collection->fields[$field] = new Field($this, $field, isset($this->collection->data[$field]) ? $this->collection->data[$field] : null, $field);
		return $this->collection->fields[$field];
	}
	
	public function __call($method, $arguments) {
		
		/**
		 * Validate a value
		 */
		array_unshift($arguments, $this->value);
		array_unshift($arguments, $this);
		call_user_func_array(array(e::$events, 'validate_' . $method), $arguments);
		
		return $this;
	}
}