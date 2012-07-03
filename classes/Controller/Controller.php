<?php

class Controller {
	private static $preFilters = array(
		'FilterSubDir',
		'FilterRoutes',
	);
	private static $postFilters = array(
	);
	private static $ClassSynonyms = array(
	);
	private static $MethodSynonyms = array(
	);
	private static $CustomHeaders = array();
	private static $DebugText = array();
	
	public static function addPreFilters () {
		$filters = func_get_args();
		foreach ($filters as $filter) {
			self::$preFilters[] = $filter;
		}
	}
	
	public static function addPostFilters () {
		$filters = func_get_args();
		foreach ($filters as $filter) {
			self::$postFilters[] = $filter;
		}
	}
	
	/**
	 * Primary Public Method
	 * Translates an incoming request into a response
	 * 
	 * @param Request $Request The Request (raw | un-filtered)
	 * @return Response
	 */
	public static function handleRequest (Request $Request) {
		$Response = new AppResponse($Request);
		$User = UserFactory::build();
		self::filterWith(self::$preFilters, $Request, $Response, $User);
		self::determineResponseType($Request, $Response);
		self::executeApplicationCall(self::prepareApplicationCall($Request, $Response, $User), $Response);
		self::filterWith(self::$postFilters, $Request, $Response, $User);
		return $Response;
	}
	
	protected static function filterWith($filter, Request $Request, Response $Response, User $User) {
		if (is_array($filter)) {
			foreach ($filter as $f) {
				self::filterWith($f, $Request, $Response, $User);
			}
			return;
		}
		if (is_string($filter)) {
			$filter = new $filter;
		}
		if (!is_a($filter, 'Filter')) throw new ExceptionBase('Invalid Filter: ' . $Filter);
		$filter->filter($Request, $Response, $User);
		return;
	}
	
	/**
	 * Determines the Appropriate Response Type Based on GET Params
	 * 
	 * @param array $get GET Parameters
	 * @return string Appropriate Response Type
	 */
	public static function determineResponseType(Request $Request, Response $Response) {
		switch ($Request->get('requestType', 'html')) {
			case "api":
			case "ajax": $rt = Response::TYPE_JSON; break;
			case "xml": $rt = Response::TYPE_XML; break;
			case "html":
			default: $rt = Response::TYPE_HTML;
		}
		$Response->type = $rt;
	}
	
	/**
	 * Executes the given call
	 * Catches and Logs Exceptions
	 */
	protected static function executeApplicationCall(stdClass $Parts, Response $Response) {
		try {
			call_user_func_array(array($Parts->class, 'invoke'), array($Parts->method, $Parts->arguments));
		} catch (ExceptionBase $E) {
			$lid = ModelLog::mkLog($E->getInternalMessage(), get_class($E), $E->getCode(), $E->getFile(), $E->getLine());
			$Response->set('errors', array(sprintf($E->getMessage(), $lid)));
			$Response->setException($E);
		} catch (Exception $E) {
			$lid = ModelLog::mkLog($E->getMessage(), get_class($E), $E->getCode(), $E->getFile(), $E->getLine());
			$Response->set('errors', array(sprintf(ExceptionBase::getPublicMessage(), $lid)));
			$Response->setException($E);
		}
	}
	
	/**
	 * Primary Handler For POST Requests
	 * Throws a Location header and exits
	 * and should set a $_SESSION['msg'] or $_SESSION['fmsg']
	 * 
	 * @param Request $Request
	 * @param Response $Response
	 * @return void
	 */
	protected static function handlePost(Request $Request, Response $Response) {
	}

	/**
	 * Translate the Request into an Application Call
	 * Translates Synonyms, return is ready to call as is
	 * 
	 * @param Request $Request The Request (filtered)
	 * @param Response $Response
	 * @return stdClass
	 */
	public static function prepareApplicationCall (Request $Request, Response $Response, User $User) {
		$Parts = new stdClass;
		$Parts->class = new ControllerPages($Request, $Response, $User);
		$Parts->method = 'notFound';
		$Parts->arguments = array();
		$elems = explode('/', trim($Request->getURI(), '/'));
		if (count($elems) > 1) {
			list($c, $m) = array_slice($elems, 0, 2);
			$Parts->arguments = array_slice($elems, 2);
		} else {
			$c = array_shift($elems);
			$m = 'index';
		}
		$c = 'Controller' . $c;
		if (array_key_exists($c, self::$ClassSynonyms)) $c = self::$ClassSynonyms[$c];
		if (class_exists($c)) {
			$tempC = new $c($Request, $Response, $User);
			if (!is_a($tempC, 'ControllerApp')) throw new ExceptionClear('Invalid Controller invoked: ' . $c . '; needs to be a subclass of ControllerApp');
			$tempM = $m;
			while (!method_exists($tempC, $tempM) && array_key_exists($m, self::$MethodSynonyms)) {
				$m = self::$MethodSynonyms[$m];
				$tempM = $prefix . $m;
			}
			if (in_array($tempM, get_class_methods($tempC))) {
				//Looks Good
				$Parts->class = $tempC;
				$Parts->method = $tempM;
			} elseif (method_exists($tempC, 'catchAll')) {
				$Parts->class = $tempC;
				$Parts->method = 'catchAll';
				array_unshift($Parts->arguments, $tempM);
			} else {
				//Method $tempM Not Found
			}
		} else {
			//Class $c Not Found
		}
		//vdump($Request->getURI(), $c, $m, $elems, $C, $M);
		return $Parts;
	}

}

?>