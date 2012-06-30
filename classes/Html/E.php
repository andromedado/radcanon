<?php
defined('PaZsCA8p') or die('Include Only');
class HtmlE extends Html {
	public function __construct($attrs=array(),$inner='', Html $appendTo = NULL){
		parent::__construct($this->tag,$attrs,$inner,$appendTo);
	}
}
?>