<?php
defined('PaZsCA8p') or exit;

abstract class UtilsHtm {
	
	/**
	 * Get an HTML Input
	 */
	public static function input($info, $prefillWith = '') {
		$i = Html::n('input', $info, '');
		if ($i->type === '') $i->type = 'text';
		if (is_array($prefillWith)) {
			$val = empty($prefillWith[$i->name]) ? '' : $prefillWith[$i->name];
		} else {
			$val = $prefillWith;
		}
		return $i->value($val);
	}
	
	/**
	 * @return Html
	 */
	public static function select(array $array, $info, $sel = 'yahoo', $selectOne = NULL){
		$O = Html::n('select', $info);
		if (!is_null($selectOne)) {
			$o = Html::n('option', '', $selectOne, $O)->value('');
		}
		foreach($array as $key=>$val){
			$o = Html::n('option','',$val,$O)->selected($key===$sel)->value($key);
		}
		return $O;
	}
	
	public static function ynSelect ($attr, $selected = 'yahoo', $opts = NULL, $selectOne = NULL) {
		if (is_null($opts)) {
			$opts = array('yes' => 'Yes', 'no' => 'No');
		}
		return self::select($opts, $attr, $selected, $selectOne);
	}
	
	public static function monthSelect ($sel=NULL) {
		$ms = array();
		for ($i = 1; $i < 13; $i++) {
			$k = $i < 10 ? '0' . $i : $i;
			$ms[$i] = $k;
		}
		return self::select($ms, 'MM', $sel);
	}
	
	public static function yearSelect($sel=NULL){
		$ys=array();
		$cy=intval(date('Y',strtotime('+1 Month')));
		if (is_null($sel)) $sel = $cy+1;
		for($i=$cy;$i<$cy+15;$i++) $ys[$i]=$i;
		return self::select($ys,'YYYY',$sel);
	}
	
}

?>