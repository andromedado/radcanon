<?php
defined('PaZsCA8p') or exit;

abstract class UtilsHttp {

	/**
	 * Throw a redirecting "Location" header
	 * @param string $loc Absolute or Relative Destination
	 * @return void
	 */
	public static function bounceTo ($loc) {
		header('Location: ' . $loc);
		exit;
	}

	/**
	 * Throw a redirecting "Location" header to the Application Root
	 * @return void
	 */
	public static function bounceToRoot () {
		header('Location: ' . APP_SUB_DIR . '/');
		exit;
	}

	public static function bounce () {
		header('Location: ' . $_SERVER['REQUEST_URI']);
		exit;
	}

}

?>