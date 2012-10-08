<?php

/**
 * Basic Requirements for a Request/Response Filter
 */
interface Filter {
	
	public function filter(Request $Request, Response $Response, User $User);
	
}

