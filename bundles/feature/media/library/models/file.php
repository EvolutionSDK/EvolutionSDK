<?php

namespace Bundles\Media\Models;
use Bundles\Media as _;
use Exception;
use stack;
use e;

class FileUploadException extends \Exception { }

class File extends \Bundles\SQL\Model {
	
	public function __set($field, $nval) {
		if($field == 'file') {
			if(is_array($nval)) {
				if((isset($nval['error']) && $nval['error'] > 0) && !isset($nval['file'])) 
					throw new FileUploadException(_\Bundle::$upload_error[$nval['error']]);
				
				$file = isset($nval['file']) ? $nval['file'] : file_get_contents($nval['tmp_name']);
				$type = $nval['type'];
				$name = $nval['name'];
			}
			
			else throw new Exception("You must pass an array containing the keys `name`, `type`, (`tmp_name` or `file` where file is an actucal result of `file_get_contents()`), and <em>optionally</em> `error` where error is a numerical representation of a file upload error as <a href=\"http://us3.php.net/manual/en/features.file-upload.errors.php\" target=\"_blank\">described here</a>");
			
			if(strpos($type, 'image') !== false) $this->filetype = 'photo';
						
			$ext = explode('.', $name);
			$ext = end($ext);
			$filename = substr(md5($name),0,5).'_'.date("Y-m-d_H-i-s").'.'.$ext;
			
			$this->filename = $filename;
			$this->filemime = ($type ? $type : _\Bundle::$mimes[$ext]);
			
			dump(e::$events->put_file($file, $filename, 'allow:'.stack::$site.'/configure/upload.yaml'));
			
			return $this->save();
		}
		
		else return parent::__set($field, $nval);
	}
	
	/**
	 * Return the file info
	 *
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function get() {
		$return = e::$events->get_file($this->filename, 'allow:'.stack::$site.'/configure/upload.yaml');
		foreach($return as $key=>$retval) if(isset($retval) && $retval != NULL && $retval != false) return $retval;
	}
	
	/**
	 * Resize/Crop Image
	 * 
	 * @param $dim
	 * @return $ptmp
	 * @author Kelly Lauren Summer Becker
	 */
	public function photo($dim) {
		$file = _\Bundle::$file_dir.'/'.$this->filename;
		return e::$media->photo($file, $dim);
	}
	
}