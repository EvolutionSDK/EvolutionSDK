<?php

namespace bundles\portal;
use e;

class Bundle {
	public static $currentPortalDir;
	public static $currentPortalName;
	
	public function __construct() {
		
		/**
		 * Add the site dir to portal locations
		 */
		e\configure::add('portal.location', e::$site);
	}

	/**
	 * Route the portal
	 */
	public function _on_router_route($path) {

		/**
		 * Check for null first segment
		 */
		if(!isset($path[0]))
			return false;

		/**
		 * Paths where this portal exists
		 */
		$matched = null;

		/**
		 * Portal Name
		 */
		$name = strtolower($path[0]);

		/**
		 * Get portal paths
		 */
		$searchdirs = e\configure::getArray('portal.location');
		
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
		 * If any paths matched
		 */
		if(!is_null($matched)) {

			/**
			 * Remove the first segment
			 */
			array_shift($path);
			
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
				e::events()->portal_route($path, $matched, "allow:$matched/portal.json");
			}
			
			/**
			 * Handle any exceptions
			 */
			catch(Exception $e) {

				/**
				 * Try to resolve with error pages
				 */
				e::events()->portal_exception($path, $matched, $e);
			}
		}
	}

	public function _on_attribute_href($url) {
		if(strpos(strtolower($url), '//') !== false
			|| strpos(strtolower($url), '/') === 0)
			return $url;
		return '/' . self::$currentPortalName . "/$url";
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
		foreach(e\configure::getArray('portal.location') as $dir) {
			
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
}
