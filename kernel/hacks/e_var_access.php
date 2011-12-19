<?php

$file = __DIR__ . '/e_var_access_generated.php';

$bundles = '';

$content = <<<_

<?php

class e_var_access {

	$bundles

}
_;

$bytes = file_put_contents($file, $content);

if(!$bytes) {
	die("<h1>Evolution</h1>Could not write bundle variable access hack, execute command: <pre>file=$file;touch \$file;chmod 777 \$file;</pre>");
}

require_once($file);