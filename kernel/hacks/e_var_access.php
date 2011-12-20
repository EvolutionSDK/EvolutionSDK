<?php

/**
 * Hack to associate $bundle static vars with bundles
 */
function e_static_bundle_access_init() {
	foreach(e_bundle_accessor::$bundleList as $name)
		e::${$name} = new e_bundle_accessor($name);
}

/**
 * Bundle static accessor pseudoclass
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
			if(!stack::$_bundle_initialized[$bundle]) {
				stack::$_bundle_initialized[$bundle] = true;
				if(method_exists(stack::$bundles[$bundle], '__initBundle'))
					stack::$bundles[$bundle]->__initBundle();
			}
			
			$bundle = $this->bundleName;
			if(method_exists(stack::$bundles[$bundle], '__getBundle'))
				$this->bundleCache = stack::$bundles[$bundle]->__getBundle();
			else
				$this->bundleCache = stack::$bundles[$bundle];
		}
	}
	
	public function __get($variable) {
		$this->__init();
		return $this->bundleCache->$variable;
	}
	
	public function __call($method, $args) {
		$this->__init();
		return call_user_func_array(array($this->bundleCache, $method), $args);
	}
}

/**
 * Load bundle names
 * TODO cache this
 */
$file = __DIR__ . '/e_var_access_generated.php';

if($_GET['_setup'] || !file_exists($file) || filesize($file) < 1) {
	$bundles = '';
	$bundleList = array();

	foreach(glob(e\sites . '/*/bundles/*/*/_bundle.php') as $name) {
		$name = strtolower(basename(dirname($name)));
		if(in_array("'$name'", $bundleList))
			continue;
		$bundleList[] = "'$name'";
		$bundles .= "\n\tpublic static $$name;";
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