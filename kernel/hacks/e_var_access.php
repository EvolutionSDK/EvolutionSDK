<?php

/**
 * Hack to associate $bundle static vars with bundles
 * @author Nate Ferrero
 */
function e_static_bundle_access_init() {
	foreach(e_bundle_accessor::$bundleList as $name)
		e::${$name} = new e_bundle_accessor($name);
}

/**
 * Utility class
 * @author Nate Ferrero
 */
class e_var_access_util {

	/**
	 * On Evolution update, clear cache
	 * @author Nate Ferrero
	 */
	public function _on_e_command_update($dir) {
		$file = "$dir/e_var_access_generated.php";
		if(is_file($file) && filesize($file) > 0) {
			file_put_contents($file, '');
			return "Cleared static bundle access cache";
		}
		else
			return "No action taken";
	}

}

/**
 * Handle events with utility class
 * @author Nate Ferrero
 */
Stack::addListener(new e_var_access_util);

/**
 * Bundle static accessor pseudoclass
 * @author Nate Ferrero
 */
class e_bundle_accessor {
	
	public static $bundleList = array();
	
	private $bundleName;
	
	private $bundleCache;
	
	public function __construct($name) {
		$this->bundleName = $name;
	}
	
	private function __init() {
		if(!isset($this->bundleCache)) {
			$bundle = $this->bundleName;
			if(!Stack::$bundleInitialized[$bundle]) {
				Stack::$bundleInitialized[$bundle] = true;
				if(method_exists(Stack::$bundles[$bundle], '__initBundle'))
					Stack::$bundles[$bundle]->__initBundle();
			}
			
			$bundle = $this->bundleName;
			$this->bundleCache = Stack::$bundles[$bundle];
		}
	}
	
	public function __isset($variable) {
		$this->__init();
		return isset($this->bundleCache->$variable);
	}
	
	public function __get($variable) {
		$this->__init();
		if(method_exists($this->bundleCache, '__getBundle')) {
			$obj = $this->bundleCache->__getBundle();
			if(!is_object($obj))
				throw new Exception("Cannot get `$variable` because bundle `$this->bundleName` did not return an object on `__getBundle`");
			return $obj->$variable;
		} else
			return $this->bundleCache->$variable;
	}

	public function __set($var, $val) {
		$this->__init();
		if(method_exists($this->bundleCache, '__getBundle')) {
			$obj = $this->bundleCache->__getBundle();
			if(!is_object($obj))
				throw new Exception("Cannot set `$var` because bundle `$this->bundleName` did not return an object on `__getBundle`");
			return $obj->$var = $val;
		} else
			return $this->bundleCache->$var = $val;
	}
	
	public function __call($method, $args) {
		$this->__init();
		if(method_exists($this->bundleCache, '__getBundle')) {
			$obj = $this->bundleCache->__getBundle();
			if(!is_object($obj))
				throw new Exception("Cannot call `$method` because bundle `$this->bundleName` did not return an object on `__getBundle`");
			return call_user_func_array(array($obj, $method), $args);
		} else
			return call_user_func_array(array($this->bundleCache, $method), $args);
	}
}

/**
 * Load bundle names
 * @author Nate Ferrero
 */
$file = e\siteCache . '/e_var_access_generated.php';

if((isset($_GET['_setup']) && $_GET['_setup']) || !file_exists($file) || filesize($file) < 1) {
	$bundles = '';
	$bundleList = array();
	$dirs = array(e\root.'/bundles', e\site.'/bundles');

	/**
	 * Get bundle libraries
	 * ====================
	 *    04 - 06 - 2012
	 * ====================
	 * @author Kelly Becker
	 */
	$constants = get_defined_constants(true);
	$EBL = array_filter(array_flip($constants['user']), function($key) {
		if(strpos($key, 'EvolutionBundleLibrary') === false)
			return false;
		else return true;
	});
	foreach(array_flip($EBL) as $EBLD)
		$dirs[] = $EBLD;

	foreach($dirs as $dir) {

		foreach(new \DirectoryIterator($dir) as $name) {
			if($name->isDot() || strlen($name) < 1) continue;

			/**
			 * If our bundle is in a Phar File
			 * @author Kelly Becker
			 */
			if(strpos($name, '.phar') !== FALSE) {
				$tfile = 'phar://' . $name->getPath().'/'.$name.'/_bundle.php';
				$name = substr($name, 0, -5);
			}

			/**
			 * Else load from a directory like normal
			 */
			else $tfile = $name->getPath().'/'.$name.'/_bundle.php';

			if(!is_file($tfile)) continue;

			$name = strtolower($name);
			if(in_array("'$name'", $bundleList))
				continue;

			$bundleList[] = "'$name'";
			$bundles .= "\n\tpublic static $$name;";
		}

	}
	
	$bundleList = implode(', ', $bundleList);

	$content = <<<_
<?php

e_bundle_accessor::\$bundleList = array($bundleList);

class e_var_access {
$bundles

}
_;

	$bytes = file_put_contents($file, $content);

	if(!$bytes) {
		die("<h1>Evolution</h1>Could not write bundle variable access hack, execute command: <pre>file=$file;touch \$file;chmod 777 \$file;</pre>");
	}
}

require_once($file);