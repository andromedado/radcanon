<?php
defined('PaZsCA8p') or die('Include Only');

class HtmlImg extends HtmlE {
	protected $tag='img';
	protected $defaultAttribute='src';
	protected $ignoreColon=true;
	
	public function __construct($attrs=array(),$inner='', Html $appendTo = NULL){
		if(!is_array($attrs) && strpos($attrs,'http:')===false){
			$attrs=Template::getStaticUrlPrefix($attrs).$attrs;
		}elseif(is_array($attrs) && isset($attrs['src']) && strpos($attrs['src'],'http:')===false){
			$attrs['src']=Template::getStaticUrlPrefix($attrs['src']).$attrs['src'];
		}
		Html::__construct($this->tag,$attrs,$inner,$appendTo);
	}
	
}

?>