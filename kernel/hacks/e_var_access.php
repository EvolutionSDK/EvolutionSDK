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
			
			/*
			$class = "Bundles\\$this->bundleName\\bundle";
			$tmp = new $class;
			
			/**
			 * Initialize bundle if needed
			 *//*
			if(method_exists($tmp, '__getBundle'))
				$this->bundleCache = $tmp->__getBundle();
			
			/**
			 * Set the effective object returned
			 *//*
			if(method_exists($tmp, '__getBundle'))
				$this->bundleCache = $tmp->__getBundle();
			else
				$this->bundleCache = $tmp;*/
			$name = $this->bundleName;
			$this->bundleCache = e::$name();
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

$bundles = '';
$bundleList = array();

foreach(glob(e\sites . '/*/bundles/*/*/_bundle.php') as $name) {
	$name = basename(dirname($name));
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

require_once($file);