<?php
if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
if (!defined('LIB_DIR')) define('LIB_DIR', dirname(__DIR__) . DS);
if (!defined('RADCANON_DIR')) define('RADCANON_DIR', __DIR__ . DS);
if (!defined('RADCANON_CSS_DIR')) define('RADCANON_CSS_DIR', RADCANON_DIR . 'css' . DS);
if (!defined('CSS_DIR')) define('CSS_DIR', RADCANON_CSS_DIR);
if (!defined('RADCANON_JS_DIR')) define('RADCANON_JS_DIR', RADCANON_DIR . 'js' . DS);
if (!defined('JS_DIR')) define('JS_DIR', RADCANON_JS_DIR);
if (!defined('RADCANON_CACHE_DIR')) define('RADCANON_CACHE_DIR', RADCANON_DIR . 'cache' . DS);
if (!defined('CACHE_DIR')) define('CACHE_DIR', RADCANON_CACHE_DIR);
if (!defined('LOG_FILE')) define('LOG_FILE', CACHE_DIR . 'rc.log');
if (!defined('MAIL_FROM')) define('MAIL_FROM', 'Info <info@radcanon.com>');
if (!defined('USE_HTACCESS')) define('USE_HTACCESS', true);
if (!defined('NO_TWIG_THANKS') && !class_exists('Twig_Autoloader')) {
	if (!defined('TWIG_LIB_DIR')) define('TWIG_LIB_DIR', LIB_DIR . 'twig' . DS . 'lib' . DS . 'Twig' . DS);
	require_once(TWIG_LIB_DIR . 'Autoloader.php');
	Twig_Autoloader::register();
}
if (!defined('RADCANON_CLASS_DIR')) define('RADCANON_CLASS_DIR', RADCANON_DIR . 'classes' . DS);
if (!defined('RADCANON_TEMPLATES_DIR')) define('RADCANON_TEMPLATES_DIR', RADCANON_DIR . 'views' . DS);
if (!defined('PaZsCA8p')) define('PaZsCA8p', 'hwllo');
if (!defined('BASE_URL')) define('BASE_URL', 'http://radcanon.com/');
if (!defined('DEFAULT_PAGE_TITLE')) define('DEFAULT_PAGE_TITLE', ' - RadCanon - ');
