<?php

namespace Bundles\Portal;
use Exception;
use stack;
use e;

class NotFoundException extends Exception {}

class Bundle {
	public static $currentPortalDir;
	public static $currentPortalName;
	
	/**
	 * Route the portal
	 */
	public function _on_router_route($path) {
		/**
		 * Add the site dir to portal locations
		 */
		e::configure('portal')->activeAdd('locations', stack::$site);
		
		/**
		 * Check for null first segment
		 */
		if(!isset($path[0]))
			$name = 'site';
			
		/**
		 * Portal Name
		 */
		else
			$name = strtolower($path[0]);

		/**
		 * Paths where this portal exists
		 */
		$matched = null;

		/**
		 * Get portal paths
		 */
		$searchdirs = e::configure('portal')->locations;
		
		/**
		 * Check for portal in paths
		 */
		foreach($searchdirs as $dir) {
			$dir .= '/portals/' . $name;
			if(is_dir($dir)) {
				$matched = $dir;
				break;
			}
		}
		
		/**
		 * Search the default portal
		 */
		if(is_null($matched)) foreach($searchdirs as $dir) {
			$name = 'site';
			
			$dir .= '/portals/' . $name;
			if(is_dir($dir)) {
				$matched = $dir;
				array_unshift($path, $name);
				break;
			}
		}
		
		/**
		 * If any paths matched
		 */
		if(!is_null($matched)) {

			/**
			 * Remove the first segment
			 */
			$shifted = array_shift($path);
			
			/**
			 * URL
			 */
			$url = implode('/', $path);
			
			/**
			 * Save current portal location
			 */
			self::$currentPortalDir = $matched;

			/**
			 * Save current portal name
			 */
			self::$currentPortalName = $name;

			try {
				
				/**
				 * Route inside of the portal
				 */
				e::$events->portal_route($path, $matched, "allow:$matched/portal.yaml");
				
				/**
				 * If nothing found, throw exception
				 */
				throw new NotFoundException("Resource `$url` not found in portal `$matched`");
			}
			
			/**
			 * Handle any exceptions
			 */
			catch(Exception $exception) {
				
				/**
				 * Try Default Portal
				 *
				 * @author Kelly Lauren Summer Becker
				 */
				if($shifted !== 'site') {
				 	array_unshift($path, 'site', $shifted);
				 	try { $this->_on_router_route($path); }
					catch(Exception $e) {}
				}
			
				/**
				 * Try to resolve with error pages
				 */
				e::$events->portal_exception($path, $matched, $exception);
				
				/**
				 * Throw if not completed
				 */
				throw $exception;
			}
		}
	}

	public function _on_attribute_href($url) {
		if(strpos($url, 'portal://') !== false)
			return str_replace('portal://', '/' . self::$currentPortalName . '/', $url);
		
		return $url;
	}

	/**
	 * Show portal directories
	 */
	public function _on_message_info() {

		/**
		 * Don't show if not in a portal
		 */
		if(self::$currentPortalDirs === null)
			return '';

		$out = '<h4>Portal Locations</h4><div class="trace">';
		foreach(e::configure('portal')->locations as $dir) {
			
			/**
			 * Get portals in dir
			 */
			$list = glob("$dir/*", GLOB_ONLYDIR);
			foreach($list as $index => $item) {
				$list[$index] = basename($list[$index]);
				if(in_array($item, self::$currentPortalDirs))
					$list[$index] = '<span class="class selected" title="This is the current portal">'.$list[$index].'</span>';
				else
					$list[$index] = '<span class="class">'.$list[$index].'</span>';
			}
			$portals = implode(' &bull; ', $list);
			if($portals != '')
				$portals = ": $portals";
			$out .= '<div class="step"><span class="file">'.$dir.$portals.'</span></div>';
		}
		$out .= '</div>';
		return $out;
	}
	
	public function currentPortalDir() {
		return self::$currentPortalDir;
	}
	
	public function currentPortalName() {
		return self::$currentPortalName;
	}
	
}
