<?php

class AppLink extends HtmlA {
	
	/**
	 * @param String|Html $word
	 * @param String|Array $location
	 * @param Boolean $relative Build the Url Relative or absolute
	 */
	public function __construct($word, $location, $relative = false) {
		if (is_array($location)) {
			$href = FilterRoutes::buildUrl($location, $relative);
		} else {
			$href = $location;
		}
		parent::__construct(array('href' => $href), $word);
	}
	
	public function setHash ($what) {
		$h = $this->attrs['href'];
		$this->href = preg_replace('/#.*$/', '', $h) . '#' . $what;
	}
	
	/**
	 * @param String|Html $word
	 * @param String|Array $location
	 * @param Boolean $relative Build the Url Relative or absolute
	 * @return AppLink
	 */
	public static function newLink($word, $location, $relative = false) {
		return new self($word, $location, $relative);
	}
	
}

?>