<?php

/**
 * URI Subdirectory Filter
 *
 * @package RAD-Canon
 * @author Shad Downey
 */
class FilterSubDir implements Filter {
	/**
	 * @return void
	 */
	public function filter(Request $req, Response $res, User $user) {
		$req->setURI(self::addressSubDir($req->getURI()));
	}
	
	public static function addressSubDir ($uri) {
		return trim(preg_replace('#^' . preg_quote(APP_SUB_DIR) . '#', '', $uri), '/ ');
	}
	
}

?>