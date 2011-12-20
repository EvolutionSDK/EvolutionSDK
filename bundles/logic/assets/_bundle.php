<?php

namespace Bundles\Assets;
use Exception;
use e;

/**
 * Enables a folder called /assets for hosting js and css and images directly out of your bundle.
 * This bundle also adds a bunch of LogicalHTML functionality. (@todo)
 *
 * @package default
 * @author David Boskovic
 */
class Bundle {
	
	public function _on_portal_route($path, $dirs) {
		
		/**
		 * Load media to instantiate the mimes
		 */
		e::$media;
		
		// Make sure path contains valid action name
		if(!isset($path[0]) || 	$path[0] !== 'assets')
			return;
			
		/**
		 * Disable Trace and Hit Count
		 */
		e\disable_trace();
		e::$session->disable_hit('child');
		
		/**
		 * Get Filename and Extension
		 */
		$filename = end($path);
		$ext = explode('.', $filename);
		$ext = end($ext);
		
		/**
		 * Get Full Filename (with path) and Path
		 */
		$icon = $path[1] === 'icons';
		$path = implode('/', $path);
		$file = $dirs.'/'.$path;
		$path = dirname($path);
		
		/**
		 * If The File Could Not Be Found Let Us Know
		 */
		if(!is_file($file)) throw new Exception("Asset `$file` could not be found.");
		
		/**
		 * If The Mime Could Not Be Found Let Us Know
		 */
		if(!isset(\Bundles\Media\Bundle::$mimes[$ext])) throw new Exception("No mime-type could be found for `$filename`");
		
		/**
		 * Compile Less
		 */
		//if($ext === 'less' && e::$environment->getVar('assets.compile.less')) $file = e::less($file);
		
		/**
		 * If Icon Process
		 */
		//if($ext === 'png' && $icon) $this->get_icon($file);
		
		/**
		 * Read File 
		 */
		header("Content-Type: ".\Bundles\Media\Bundle::$mimes[$ext]);
		readfile($file);
		
		e\complete();
	}
	
	public function get_icon($file, $color='#000', $x=100, $y=100) {
		$src = e::$media->gd($file);
		$src->resize(array('x'=>$x, 'y'=>$y));
		
		$dest = imagecreatetruecolor($x, $y);
		/**
		 * Set the Transparency Color
		 */
		$trans = imagecolorallocate($dest, 255, 255, 255);
		
		/**
		 * Get New Color
		 */
		if(!is_array($color)) $color = $this->hex2RGB($color);
		$new = imagecolorallocate($src->current, $color['r'], $color['g'], $color['b']);
		
		/**
		 * If the new color is the transparency change it
		 */
		if($new == $trans) $trans = imagecolorallocate($dest, 0, 0, 0);
		
		/**
		 * Set The Transparency
		 */
		imagecolortransparent($src->current, $trans);
		
		/**
		 * Update Icon Color
		 */
		/*for ($x = 0; $x < imagesx($src->current); $x++) {
		    for ($y = 0; $y < imagesy($src->current); $y++) {
		        $src_pix = imagecolorat($src->current,$x,$y);
		        $c = $this->rgb_to_array($src_pix);
		
				$old = imagecolorallocate($dest, $c['r'], $c['g'], $c['b']);

		        if($old !== $trans) {
					imagesetpixel($src->current, $x, $y, $new);
				}
		    }
		}
		//die;*/
		
		// Output and free from memory
		header('Content-Type: image/png');
		imagepng($src->current);
		imagedestroy($src->current);
		imagedestroy($dest);
		e\Complete();
	}
	
	private function hex2RGB($hexStr, $returnAsString = false, $seperator = ',') {
	    $hexStr = preg_replace("/[^0-9A-Fa-f]/", '', $hexStr); // Gets a proper hex string
	    $rgbArray = array();
	    if (strlen($hexStr) == 6) { //If a proper hex code, convert using bitwise operation. No overhead... faster
	        $colorVal = hexdec($hexStr);
	        $rgbArray['r'] = 0xFF & ($colorVal >> 0x10);
	        $rgbArray['g'] = 0xFF & ($colorVal >> 0x8);
	        $rgbArray['b'] = 0xFF & $colorVal;
	    } elseif (strlen($hexStr) == 3) { //if shorthand notation, need some string manipulations
	        $rgbArray['r'] = hexdec(str_repeat(substr($hexStr, 0, 1), 2));
	        $rgbArray['g'] = hexdec(str_repeat(substr($hexStr, 1, 1), 2));
	        $rgbArray['b'] = hexdec(str_repeat(substr($hexStr, 2, 1), 2));
	    } else {
	        return false; //Invalid hex color code
	    }
	    return $returnAsString ? implode($seperator, $rgbArray) : $rgbArray; // returns the rgb string or the associative array
	}
	
	private function rgb_to_array($rgb) {
	    $a['r'] = ($rgb >> 16) & 0xFF;
	    $a['g'] = ($rgb >> 8) & 0xFF;
	    $a['b'] = $rgb & 0xFF;

	    return $a;
	}
	
}
