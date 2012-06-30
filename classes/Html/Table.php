<?php
defined('PaZsCA8p') or die('Include Only');

class HtmlTable extends HtmlE {
	protected $tag='table';
	protected $innerWrapper='tbody';
	protected $NaturalChildTag='tr';//I know, I know...
	protected $defaultAttribute='class';
	
	public function __construct($attrs=array(),$inner='', Html $appendTo = NULL,$iw=NULL){
		if (!is_null($iw)) $this->innerWrapper=$iw;
		Html::__construct($this->tag,$attrs,$inner,$appendTo);
	}
	
	public function nTR($a=array(),$i=''){
		return HtmlE::n('tr',$a,$i,$this);
	}
	
	public function row(){
		$args=func_get_args();
		return call_user_func_array(array($this,'tr'),$args);
	}
	
	public function pair($th,$td){
		HtmlE::n('tr')->th($th)->td($td)->apT($this);
		return $this;
	}
	
	public function tr(){
		$args=func_get_args();
		$of=func_num_args();
		$tr=HtmlE::n('tr')->apT($this);
		$i=0;
		foreach($args as $arg){
			$i++;
			$tr->td($arg,'c:c'.$i.'of'.$of);
		}
		return $this;
	}
	
	public function td($inner='',$attrs=array()){
		HtmlE::n('tr')->apT($this)->td($inner,$attrs);
		return $this;
	}
	
	public function th($inner='',$attrs=array()){
		HtmlE::n('tr')->apT($this)->th($inner,$attrs);
		return $this;
	}
	
}

?>