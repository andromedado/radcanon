<?php
defined('PaZsCA8p') or die('Include Only');

class HtmlUl extends HtmlE {
	protected $tag='ul';
	protected $NaturalChildTag='li';
	protected $defaultAttribute='class';
	
	/**
	 * @param string|Html $inner
	 * @param string|array $attrs
	 * @return Html
	 */
	public function nLI($inner='',$attrs=array()){
		return HtmlE::n('li',$attrs,$inner,$this);
	}
	
}

?>