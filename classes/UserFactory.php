<?php

abstract class UserFactory
{
	private static $Hierarchy = array(
//		'SuperAdmin',
//		'Admin',
//		'AuthUser',
		'User',
	);
	
	/**
	 * @return User
	 */
	public static function build () {
		$User = NULL;
		foreach (self::$Hierarchy as $Class) {
			$O = new $Class;
			if ($O->isValid()) {
				$User = $O;
				break;
			}
		}
		return $User;
	}
	
}

