<?php
if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
if (!defined('LIB_DIR')) define('LIB_DIR', dirname(__DIR__) . DS);
if (!defined('RADCANON_DIR')) define('RADCANON_DIR', __DIR__ . DS);
if (!defined('RADCANON_CACHE_DIR')) define('RADCANON_CACHE_DIR', RADCANON_DIR . 'cache' . DS);
if (!defined('CACHE_DIR')) define('CACHE_DIR', RADCANON_CACHE_DIR);
if (!defined('MAIL_FROM')) define('MAIL_FROM', 'Info <info@radcanon.com>');
if (!defined('USE_HTACCESS')) define('USE_HTACCESS', true);
if (!class_exists('Twig_Autoloader')) {
	if (!defined('TWIG_LIB_DIR')) define('TWIG_LIB_DIR', LIB_DIR . 'twig' . DS . 'lib' . DS . 'Twig' . DS);
	require_once(TWIG_LIB_DIR . 'Autoloader.php');
	Twig_Autoloader::register();
}
if (!defined('RADCANON_CLASS_DIR')) define('RADCANON_CLASS_DIR', RADCANON_DIR . 'classes' . DS);
if (!defined('RADCANON_TEMPLATES_DIR')) define('RADCANON_TEMPLATES_DIR', RADCANON_DIR . 'views' . DS);
?>