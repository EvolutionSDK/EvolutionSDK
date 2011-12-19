<?php

namespace Bundles\Validator;
use Exception;
use e;

/**
 * Validator Bundle
 */
class Bundle {

	public function __bundle_response() {
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
			$field->cleanValue($ref);
		}
	}
}

/**
 * Setup field class
 */
class Collection {
	
	private $data = array();
	
	private $messages = array();
	
	private $fields = array();
	
	public function getMessages() {
		return $this->messages;
	}
	
	public function broadcastMessages() {
		foreach($this->messages as $message)
			e::events()->message($message);
		$this->messages = array();
	}
	
	public function printMessages() {
		if(!defined('BUNDLE_field_MESSAGE_PRINTED_STYLE')) {
			echo <<<_
<style>
ul.validator-messages {
	margin: 0;
	padding: 0;
	list-style-type: none;
}
ul.validator-messages li {
	margin: 0 0 1em 0;
	padding: 0.5em;
	
	border: 1px solid #888;
	background: #eee;
	color: #666;
}
ul.validator-messages li.message-error {
	border: 1px solid #600;
	background: #fcc;
	color: #600;
}
ul.validator-messages li span.field {
	font-weight: bold;
}
</style>
_;
			define('BUNDLE_VALIDATOR_MESSAGE_PRINTED_STYLE', 1);
		}
		echo '<ul class="validator-messages">';
		foreach($this->messages as $message) {
			echo '<li class="message-' . $message['type'] . '">' . $message['message'] . '</li>';
		}
		echo '</ul>';
	}
	
	public function __construct($sources) {
		foreach($sources as $data) {
			
			/**
			 * Add source elements to data
			 */
			if(is_array($data)) {
				foreach($data as $key => $value) {
					$this->data[$key] = $value;
				}
			}
		}
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
			$clean[$field->getField()] = $field->getCleanValue();
		return $clean;
	}
	
	public function addMessage($type, $message, $field = null) {
		$this->messages[] = array('type' => $type, 'message' => $message, 'field' => $field);
	}
}

/**
 * Single value validation
 */
class Field {
	
	private $value;
	
	private $clean;
	
	private $field;
	
	private $collection;
	
	private $humanReadableName;
	
	public function __construct($collection, $field, $value) {
		$this->collection = $collection;
		$this->field = $field;
		$this->humanReadableName = ucwords(str_replace(array('-', '_', '.'), array(' ', ' ', ' '), $field));
		$this->value = $value;
		$this->clean = $value;
	}
	
	public function raw() {
		return $this->value;
	}
	
	public function getField() {
		return $this->field;
	}
	
	public function getCleanValue() {
		return $this->clean;
	}
	
	public function cleanValue($value) {
		$this->clean = $value;
	}
	
	public function error($message) {
		$message = str_replace('%field', '<span class="field">' . $this->humanReadableName . '</span>', $message);
		$this->collection->addMessage('error', $message, $this->field);
	}
	
	public function setHumanReadableName($name) {
		$this->humanReadableName = $name;
	}
	
	public function __call($method, $arguments) {
		
		/**
		 * Validate a value
		 */
		array_unshift($arguments, $this->value);
		array_unshift($arguments, $this);
		call_user_func_array(array(e::events(), 'validate_' . $method), $arguments);
		
		return $this;
	}
}