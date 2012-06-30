<?php

function radcanon_autoloader ($classname) {
	$cLoc = $classname = preg_replace('/[^A-Z\d_-]+/i', '', $classname);
	$prefixes = array(
		'App',
		'AuthNet',
		'File',
		'Html',
		'Exception',
		'Filter',
		'Model',
		'Controller',
		'Email',
		'Utils',
	);
	foreach ($prefixes as $prefix) {
		if (preg_match('#^' . $prefix . '#', $classname)) {
			$cLoc = preg_replace('#^' . $prefix . '#', $prefix . DS, $classname);
			if ($cLoc === $prefix . DS) $cLoc .= $classname;
			break;//May only be performed once!
		}
	}
	if (!file_exists(RADCANON_CLASS_DIR . $cLoc . '.php')) {
		return false;
	}
	require_once(RADCANON_CLASS_DIR . $cLoc . '.php');
	return true;
}

function vdump () {
	if (defined('DEBUG') && DEBUG) {
		$i = 1;
		while (ob_get_level() && $i < 5) {
			ob_end_flush();
			$i++;
		}
		$args = func_get_args();
		echo '<html><head></head><body><pre>';
		call_user_func_array('var_dump', $args);
		echo '</pre></body></html>';
		exit;
	}
}

?>