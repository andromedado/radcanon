<?php

class UtilsApp {
	
	/**
	 * Is the given argument 'empty'?
	 * @return bool
	 */
	public static function isEmpty($arg) {
		return empty($arg);
	}
	
	/**
	 * 
	 * @return Html
	 */
	public static function getLinkedAddButton() {
		$I = Html::n('img', 'images/add.png')->alt("Add")->class('admin_only_inline');
		return Html::n('a', 'javascript:void(0)', $I);
	}
	
}

?>