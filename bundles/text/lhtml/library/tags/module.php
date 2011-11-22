<?php

namespace Bundles\LHTML;
use Exception;

class Node_Module extends Node {	
	
	
	public function init() {
		$this->element = false;
	}
	
	public $_configure = array();
	public $_showme = true;

	private $_options = false;
	
	public function _options() {
		if($this->_options) return $this->_options;
		
		$configure = $this->_children(':configure', 1);
        if(!is_object($configure))
        	e::fault(50, 'ixml_module_no_config', array('module' => 'name'));
		
		# load up the options
		$options = (string) $configure->_children('options',1);
		$options = e::helper('yaml')->string($options);
		
		$layout = $this->_layout();

		$loptions = (string) $layout->_children(':options',1);
		$loptions = e::helper('yaml')->string($loptions);
		$layout->_delete(':options');

		# now merge our options into a single set.
		if(is_array($loptions)) {
			$options = array_merge($options, $loptions);
		}
		$options['_name'] = (string) $configure->_children('name',1);
		$options['_description'] = (string) $configure->_children('description',1);
		
		$mt = @$layout->attr['module_type'];
		$mt = $options['module_type'] == '*' || empty($options['module_type']) ? false : $options['module_type'];
		if(!$mt) {
			$options['module_type'] = array('choice', 'default', 'Module Type', array('default' => 'Default Style'));
		}
		$layouts = $this->_children(':layouts', 1)->_children(':layout');
		$layout_select = array();
		$ldefault = false;
		foreach($layouts as $nl) {
			if($nl->attr['default']) $ldefault = $nl->attr['slug'];
			$layout_select[$nl->attr['slug']] = $nl->attr['name'];
		}
		$options['layout'] = array('choice', $ldefault, 'Choose Layout Format', $layout_select);
		
		$this->_options = $options;
		
		return $options;
	}
	
	public function _admin_form($id = false) {
		$options = $this->_options();
		$values = $this->_option_values(true);
		
		$html = '<div class="_config_module_holder" id="_module_'.$id.'">
				<form class="_config_module_form"><input type="hidden" name="_update_config" value="1" />';
		foreach($values as $key => $option) {
			$config = @$options[$key];
			list($type, $default, $label, $select) = $config;
            if(is_array($option))
                $option = serialize($option);
			$value = htmlentities($option);
			switch($type) {
				case 'text':
					$html .= "<label>$label</label><input name=\"$key\" type=\"text\" value=\"$value\" />"; 
				break;
				case 'switch':
					$html .= "<label><input name=\"$key\" type=\"checkbox\" ".($value ? 'checked="checked"' : '')." /> $label</label>";
				break;
				case 'number':
					$html .= "<label>$label</label><input name=\"$key\" type=\"text\" value=\"$value\" />";
				break;
				case 'choice':
					$html .= "<label>$label</label><select name=\"$key\"> ";
					foreach($select as $skey => $svalue) {
						$sel = $skey == $value ? ' selected="selected"' : '';
						$html .= '<option value="'.$skey.'"'.$sel.'>'.$svalue.'</option>';
					}
					$html .= '</select>';
				break;
				default:
					if(!$config) $html .= "<input name=\"$key\" type=\"hidden\" value=\"$value\" />"; 
					
				break;
			}
			//if(is_string($config)) $html .= "<input name=\"$key\" type=\"hidden\" value=\"$value\" />"; 
			
		}
		$html .= '<div style="margin-top:20px">
			<input type="submit" value="Update Configuration" />
		</div></form></div>';
		return $html;
	}
	
	
	private $_option_values = false;
	private $_literal_option_values = false;
	
	public function _option_values($literal = false) {
		if(!$literal && $this->_option_values) return $this->_option_values;
		if($literal && $this->_literal_option_values) return $this->_literal_option_values;
		
		$vals = (array) $this->_config();
		$options = array();
		foreach($this->_options() as $key => $option) {
			$options[$key] = isset($vals[$key]) ? $vals[$key] : (!is_array($option) ? $option : $option[1]);
		}
		$all = array_merge($vals, $options);
	
		if(!$literal) {
			$parsed = array();
			foreach($all as $key => $v) {
				# Extract variables in options if not literal
				$vars = $this->extract_vars($v);
				if($vars) {
					foreach($vars as $var) {
						$data_response = ($this->_data()->$var);	
						$v = str_replace('{'.$var.'}', $data_response, $v);				
					}				
				}
				$parsed[$key] = $v;
			}
			
			$this->_option_values = $parsed;
			return $this->_option_values;
		} else {
			$this->_literal_option_values = $all;
			return $this->_literal_option_values;
		}
	}
	private $_config = false;
	public function _config() {
		if($this->_config) return $this->_config;
		$src = $this->attr['_config'];
		$this->_config = array();
		if(!is_array($src)) $src = e::$helper->yaml->string($src); 
		if(is_array($src)){
			foreach($src as $key => $el) {
				$this->_config[$key] = (is_array($el) ? $el[0] : $el);
			}
		}
		unset($this->attr['_config']);
		return $this->_config;
	}
	private $_layout = false;
	public function _layout() {
		if($this->_layout) return $this->_layout;
		$layouts = $this->_children(':layouts', 1)->_children(':layout');
		
		
		$config = $this->_config();
		# load up the right layout option.
		$lslug = !empty($config['layout']) ? $config['layout'] : false;
		foreach($layouts as $layout) {
			if($layout->attr['slug'] == $lslug || (!$lslug && !empty($layout->attr['default'])))
				break;
		}

		if(!$layout)
			e::fault(50, 'ixml_module_no_layout_selected');
		$this->_layout = $layout;
		return $layout;
	}
	
	public function initialize() {
		$array = $this->attr;
		if(isset($array['name'])) {
			
			/**
			 * Load up our module file.
			 */	
			
			$dir = e::$url->portal ? ROOT_PORTALS.'/'.e::$url->portal.'/modules/' : ROOT_INTERFACE.'/_modules/';
			if(isset($array['portal'])) $dir = ROOT_PORTALS.'/'.$array['portal'].'/modules/';
			$parse = new Interface_Parser($dir.$this->attr['name'].'.ixml', $this);			
		}
		
	}
	public function _ignore(){
		if(is_null($this->_children(':ignore',1)))return false;
		return true;
	}
	public function _valid_owners() {
		

		
		$configure = $this->_children(':configure', 1);
        if(!is_object($configure))
        	e::fault(50, 'ixml_module_no_config', array('module' => 'name'));
		
		# get the valid owners
		$owners = (string) $configure->_children('valid_owners',1);
		
		if($owners=='')
			e::fault(50, 'ixml_module_no_owner_config');
		$owners = json_decode($owners);
		return $owners;
	}

	/**
	 * When this element is turned into html, we do all the processing so we can produce a beautiful form.
	 *
	 * @return string
	 * @author David D. Boskovic
	 */
	public function build() {
		
		/**
		 * Make sure we have the basic attributes we need.
		 */
		if(!isset($this->attr['name']))
			throw new \Exception("Trying to create a <:module... tag without the name attribute.");		
		
		

		# get the configure element
		$configure = $this->_children(':configure', 1);
        if(!is_object($configure))
    		e::fault(50, 'ixml_module_no_config', array('module' => 'name'));
            
    	# get the valid owners
        $owners = $configure->_children('valid_owners',1);
		$owners = (string) $owners;
		if(!$owners)
			e::fault(50, 'ixml_module_no_owner_config');

		$owners = json_decode($owners);
		if(!$owners)
			e::fault(50, 'ixml_module_invalid_owner_config');
		
			
		$this->_configure['name'] = (string) $configure->_children('name',1);
		$this->_configure['description'] = (string) $configure->_children('description',1);
		
		$layout = $this->_layout();
		$this_owner = false;
		$null_allowed = true;
		$config = $this->_config();
		if(!$config['_ignore_owner']) {
			$null_allowed = false;
			foreach($owners as $owner) {
				if($owner) {
					if($layout->_data()->$owner) {
						$this_owner = $owner;
						break;
					}					
				}
				else {
					$null_allowed = true;
				}
			}
		}
		if(!$this_owner && $null_allowed)
			$owner = new emptyOwner;
		else
			$owner = $layout->_data()->$this_owner;

		$layout->_data = new InterfaceHelper_Scope($this->_data());
		$layout->_data->data[':owner'] = $owner;
		$layout->_data->data[':admin_form'] = $this->_admin_form();
		$layout->_data->data[':option'] = $this->_option_values();
		
		$display_callback = $configure->_children('display-callback',1);
		if($display_callback && $display_callback->attr['if']) {
			$var = ':controller.portal';
			$callback = $display_callback->attr['if'];
			
			$obj = ($this->_data()->$var);
			$r = $obj->$callback($layout->_data);
			$this->_showme = $r;
		}
		
			if(!$this->_showme) return '';
		return (string) $layout;
	}
	
	
}

class emptyOwner {
	public function __call($method, $args) {
		//v($method);die;
		return e::app($method);
	}
}