<?php

namespace Bundles\LHTML;
use Exception;

class Node_Youtube extends Node {
	
	public function init() {
		$this->element = false;
	}
	
	public function build() {
		/**
		 * Get the Youtube ID
		 */
		if(isset($this->attributes['src']) && strpos($this->attributes['src'], '?') !== false) {
			$src = explode('?', $this->attributes['src']);
			parse_str(end($src), $src);
			$src = 'http://www.youtube.com/embed/'.$src['v'];
		}
		
		else if(isset($this->attributes['src']) && strpos($this->attributes['src'], 'youtu.be') !== false) {
			$src = explode('/', $this->attributes['src']);
			$src = end($src);
			$src = 'http://www.youtube.com/embed/'.$src;
		}
		
		else if(isset($this->attributes['src']) && strpos($this->attributes['src'], 'embed') !== false) {
			$src = $this->attributes['src'];
		}
		
		else return "YouTube Video Could Not Be Loaded.";
	
		/**
		 * Set the width and height
		 */
		$width = '560';
		$height = '315';
		if(isset($this->attributes['width'])) $width = $this->attributes['width'];
		if(isset($this->attributes['height'])) $height = $this->attributes['height'];
		
		/**
		 * Return the rendered string
		 */
		return "<iframe width=\"$width\" height=\"$height\" src=\"$src\" frameborder=\"0\" allowfullscreen></iframe>";
		
	}
		
}