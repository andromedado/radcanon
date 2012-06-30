<?php
defined('PaZsCA8p') or die('Include Only');
/*
 * HtmlCollection - Used to hold groups of Html's,
 * but doesn't add anything to them when cast as a string
 * 
 */
class HtmlC extends Html {
	/**
	 * Can be passed any number of parameters,
	 * all of which will be left intact, and 
	 * appended in order to this HtmlC
	 */
	public function __construct(){
		$args = func_get_args();
		foreach($args as $arg){
			$this->a($arg);
		}
	}
	
	/** @Override */
	public function __toString(){
		return self::stringify($this->innerA);
	}
	
}
?>