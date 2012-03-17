<?php

namespace Bundles\Yaml;

class Yaml_Result {
	
	protected $file;
	
	public $_date_modified;
	public $_date_cached;
	
	private $_modified = false;
	
	private $_data;
	
	public function __construct($file) {
		$this->$file = $file;
		
		$this->_data = $this->_objectize(Bundle::load($file, true));
		
		$modified = Bundle::last_modified($file);
		
		$this->_date_modified = date("Y-m-d h:i:s", $modified['yaml']);
		$this->_date_cached = date("Y-m-d h:i:s", $modified['cache']);
	}
	
	private function _objectize(array $array){
        foreach($array as $key => $value){
            if(is_array($value)){
                $array[$key] = $this->_objectize($value);
            }
        }

        return (object) $array;
	}
	
	private function _arrayize(stdClass $Class){
        $class = (array) $class;

        foreach($class as $key => $value){
            if(is_object($value) && get_class($value) === 'stdClass'){
                $class[$key] = $this->_arrayize($value);
            }
        }

        return $class;
	}
	
	public function __get($var) {
		return $_data->$var;
	}
	
	public function __set($var, $val) {
		if(!is_string($var)) return false;
		
		if(is_string($val)) $this->_data->$var = $val;
		if(is_array($val)) $this->_data->$vat = $this->_objectize($val);
		else $this->$var = $val;
		
		$this->_modified = true;
	}
	
	public function save() {
		Bundle::save($file, $this->_arrayize($this->_data));
	}
	
	public function dump() {
		return Bundle::dump($this->_arrayize($this->_data));
	}
	
	public function __toArray() {
		return $this->_arrayize($this->_data);
	}
	
	public function __destruct() {
		$this->save();
	}
	
}