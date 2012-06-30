<?php
defined('PaZsCA8p') or die('Include Only');

class HtmlTr extends HtmlE {
	protected $tag='tr';
	protected $NaturalChildTag='td';
	protected $defaultAttribute='class';
	
	public function th($con='',$attrs=array()){
		HtmlE::n('th',$attrs,$con,$this);
		return $this;
	}
	
}

?>