<?php

/**
 * Basic Requirements for a Request/Response Filter
 */
class FilterIpLog implements Filter {
	
	public function filter(Request $Request, Response $Response, User $User) {
		$pip = $Request->server('REMOTE_ADDR', '0.0.0.0');
		$ILM = ModelIpLog::findOne(array(
			'fields' => array(
				'pip' => $pip,
			),
		));
		$ILM->saveData(array(
			'ip' => sprintf("%u", ip2long($pip)),
			'pip' => $pip,
			'user_agent' => $Request->server("HTTP_USER_AGENT", 'empty'),
			'hits' => $ILM->hits + 1,
			'last_hit' => time(),
			'reputation' => $ILM->reputation,
		));
	}
	
}

?>