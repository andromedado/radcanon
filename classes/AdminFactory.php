<?php

abstract class AdminFactory {
	
	/**
	 * @return ModelAdmin
	 */
	public static function build ($id = NULL) {
		$O = new ModelSuperAdmin($id);
		if (!$O->isValid()) {
			$O = new ModelAdmin($id);
		}
		return $O;
	}
	
}

?>