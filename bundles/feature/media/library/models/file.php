<?php

namespace Bundles\Media\Models;
use Bundles\Media as _;
use Exception;
use e;

class FileUploadException extends \Exception { }

class File extends \Bundles\SQL\Model {
	
	public function __set($field, $nval) {
		if($field == 'file') {
			if(file_exists($nval['file']['tmp_name'])) {
				if($nval['file']['error'] > 0) throw new FileUploadException(_\Bundle::$upload_error[$nval['file']['error']]);
				$file = file_get_contents($nval['file']['tmp_name']);
				$type = $nval['file']['type'];
				$name = $nval['file']['name'];
			}
			
			else if(is_array($nval)) {
				$file = $nval['file'];
				$type = $nval['type'];
				$name = $nval['name'];
			}
			
			if(strpos($type, 'image') !== false) $this->filetype = 'photo';
						
			$ext = explode('.', $name);
			$ext = end($ext);
			$filename = substr(md5($name),0,5).'_'.date("Y-m-d_h-i-s").'.'.$ext;
			
			$this->filename = $filename;
			$this->filemime = ($type ? $type : _\Bundle::$mimes[$ext]);
			
			e::events()->put_file($file, $filename, 'allow:'.e::$site.'/configure/upload.yaml');
			
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
		$return = e::events()->get_file($this->filename, 'allow:'.e::$site.'/configure/upload.yaml');
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
		return e::media()->photo($file, $dim);
	}
	
}