<?php

class Controller {
    private static $preFilters = array(
        'FilterSubDir',
        'FilterRoutes',
    );
    private static $postFilters = array(
    );
    private static $ControllerSynonyms = array(
    );
    private static $MethodSynonyms = array(
    );
    private static $CustomHeaders = array();
    private static $DebugText = array();
    /** @var User $theUser */
    private static $theUser = null,
        $fallback = array(
        'controller' => 'ControllerPages',
        'method' => 'notFound',
        'defaultArgs' => array(),
    );

    public static function addControllerSynonym ($from, $to = NULL) {
        if (is_null($to) && is_array($from)) {
            self::$ControllerSynonyms = array_merge(self::$ControllerSynonyms, $from);
        } else {
            self::$ControllerSynonyms[$from] = $to;
        }
    }

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
     * @return User
     */
    public static function getUser()
    {
        if (is_null(self::$theUser)) {
            self::$theUser = UserFactory::build();
        }
        return self::$theUser;
    }

    /**
     * Primary Public Method
     * Translates an incoming request into a response
     *
     * @param Request $Request The Request (raw | un-filtered)
     * @return Response
     */
    public static function handleRequest (Request $Request) {
        if (DEBUG && $Request->get('php_info', '') === 'true') {
            phpinfo();exit;
        }
        $Response = new AppResponse($Request);
        $User = self::getUser();
        self::filterWith(self::$preFilters, $Request, $Response, $User);
        //If a filter made this into a bounce, no need to execute an application call
        if ($Response->type !== Response::TYPE_LOCATION) {
            try {
                self::determineResponseType($Request, $Response);
                self::executeApplicationCall(self::prepareApplicationCall($Request, $Response, $User), $Response);
            } catch (ExceptionReroute $e) {
                $Request->setURI($e->getUri());
                self::determineResponseType($Request, $Response);
                self::executeApplicationCall(self::prepareApplicationCall($Request, $Response, $User), $Response);
            }
        }
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
        if (!is_a($filter, 'Filter')) throw new ExceptionBase('Invalid Filter: ' . $filter);
        /** @var Filter $filter */
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
        switch ($Request->get('requestType', $Request->post('requestType', 'html'))) {
            case "api":
            case "ajax": $rt = Response::TYPE_JSON; break;
            case "xml": $rt = Response::TYPE_XML; break;
            case "html":
            default: $rt = $Request->isPost() ? Response::TYPE_LOCATION : Response::TYPE_HTML;
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
        } catch (ExceptionReroute $E) {
            throw $E;
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
     * Set the fallback controller method to use
     * Normally a 404 page
     * @param string $controllerClass
     * @param string $method
     * @param array $defaultArgs
     */
    public static function setFallback($controllerClass, $method = 'index', array $defaultArgs = array())
    {
        self::$fallback = array(
            'controller' => $controllerClass,
            'method' => $method,
            'defaultArgs' => $defaultArgs,
        );
    }

    protected static function getFallbackAction(Request $Request, Response $Response, User $User)
    {
        $Parts = new stdClass;
        $class = self::$fallback['controller'];
        $Parts->class = new $class($Request, $Response, $User);
        $Parts->method = self::$fallback['method'];
        $Parts->arguments = self::$fallback['defaultArgs'];
        return $Parts;
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
        $Parts = self::getFallbackAction($Request, $Response, $User);
        $elements = explode('/', trim($Request->getURI(), '/'));
        if (count($elements) > 1) {
            list($c, $m) = array_slice($elements, 0, 2);
            $Parts->arguments = array_slice($elements, 2);
        } else {
            $c = array_shift($elements);
            $m = 'index';
        }
        if (array_key_exists($c, self::$ControllerSynonyms)) $c = self::$ControllerSynonyms[$c];
        $c = 'Controller' . $c;
        if (class_exists($c)) {
            $tempC = new $c($Request, $Response, $User);
            if (!is_a($tempC, 'ControllerApp')) throw new ExceptionClear('Invalid Controller invoked: ' . $c . '; needs to be a subclass of ControllerApp');
            $tempM = $m;
            while (!method_exists($tempC, $tempM) && array_key_exists($m, self::$MethodSynonyms)) {
                $tempM = $m = self::$MethodSynonyms[$m];
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
        //vdump($Request->getURI(), $c, $m, $elements, $C, $M);
        return $Parts;
    }

}

