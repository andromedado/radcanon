<?php

class FilterMemoryLog implements Filter
{
	
	public function filter(Request $Request, Response $Response, User $User)
	{
		$info = array(
			'iniUri' => $Request->getIniURI(),
			'uri' => $Request->getURI(),
			'memory' => number_format(memory_get_usage()),
			'memory_peak' => number_format(memory_get_peak_usage()),
			'db_queries' => Request::getInfo('db_queries'),
		);
		ModelLog::mkLog($info, 'memory', '0', __FILE__, __LINE__);
	}
	
}

