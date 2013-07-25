<?php

/**
 * Response Object
 *
 * @package RAD-Canon
 * @author Shad Downey
 * @property String $template
 * @property String $content
 * @property String $error
 * @property String $type
 */
class Response {
    const TYPE_HTML = 0;
    const TYPE_JSON = 1;
    const TYPE_XML = 2;
    const TYPE_LOCATION = 3;
    const TYPE_CSV = 4;
    const TYPE_FILESTREAM = 5;
    const TYPE_TEMPLATE_IN_JSON = 6;
    const TYPE_RAW_ECHO = 7;
    const TYPE_EMPTY = 8;

    /**@var Request $request */
    protected $request = null;
    protected $type = 0;
    protected $error = false;
    protected $headers = array();
    protected $tplDirs = array();
    /** @var Exception $exception */
    protected $exception = null;
    protected $location = null;
    protected $redirectCode = 302;
    protected $contentType = null;
    protected $content = null;
    protected $invocation = null;
    protected $filename = null;
    /** @var Twig_Environment */
    protected $TwigEnvironment = null;
    protected $template = 'base.html.twig';
    protected $defaultVars = array(
        'styles' => array(),
        'scripts' => array(),
        'title' => DEFAULT_PAGE_TITLE,
        'content' => '',
        'metas' => '',
        'baseHref' => BASE_URL,
        'app_sub_dir' => APP_SUB_DIR,
    );
    protected $appVars = array();
    protected $vars = array();

    protected $public = array(
        'template',
        'content',
        'error',
        'redirectCode',
        'contentType',
        'type',
        'filename',
    );

    protected $viewable = array(
    );

    protected static $ContentTypes = array(
        'text/html',
        'text/json',
        'text/xml',
        null,
        'text/csv',
    );

    public function __construct(Request $req) {
        $this->request = $req;
        $this->location = $req->post('_bounceBack', $req->server('REQUEST_URI', APP_SUB_DIR . '/'));
        $this->set('currentUri', $this->request->getIniURI());
        if (!isset($_SESSION['msg'])) $_SESSION['msg'] = array();
        if (!isset($_SESSION['f_msg'])) $_SESSION['f_msg'] = array();
        if (defined('APP_TEMPLATES_DIR') && is_dir(APP_TEMPLATES_DIR)) {
            $this->tplDirs[] = APP_TEMPLATES_DIR;
        }
        $this->tplDirs[] = RADCANON_TEMPLATES_DIR;
        $this->load();
    }

    /**
     * Does the given template filename (fully qualified, but relative) exist?
     * @param String $template
     * @return Boolean
     */
    public function templateExists($template)
    {
        $exists = false;
        if (!empty($template)) {
            foreach ($this->tplDirs as $dir) {
                if ($exists = file_exists($dir . $template)) {
                    break;
                }
            }
        }
        return $exists;
    }

    protected function load() {

    }

    public function setCookie () {
        $args = func_get_args();
        call_user_func_array('setcookie', $args);
    }

    public function forceSSL () {
        if ($this->request->server('SERVER_PORT') !== '443') {
            $this->redirectTo('https://' . SITE_HOST . $this->request->getIniURI());
            $this->render();
            exit;
        }
    }

    public function forceNoSSL ()
    {
        if ($this->request->server('SERVER_PORT') === '443') {
            $this->redirectTo('http://' . SITE_HOST . $this->request->getIniURI());
            $this->render();
            exit;
        }
    }

    public function setException(Exception $e) {
        $this->exception = $e;
        if (DEBUG) {
            $this->set('_exception', $e);
            header('X-Exception: ' . str_replace("\n", '', $e->getMessage()));
            if ($e instanceof ExceptionBase) {
                header('X-Exception: ' . str_replace("\n", '', $e->getInternalMessage()));
            }
        }
        $this->error = true;
        return $this;
    }

    public function setInvocation ($This, $method, $args) {
        $this->invocation = array('This' => $This, 'Method' => $method, 'Arguments' => $args);
    }

    /**
     * @return mixed
     */
    public function __get($what) {
        if (in_array($what, $this->public) || in_array($what, $this->viewable)) return $this->$what;
        return null;
    }

    public function addS () {
        $args = func_get_args();
        call_user_func_array(array($this, 'addScript'), $args);
        call_user_func_array(array($this, 'addStyle'), $args);
        return $this;
    }

    public function addScript () {
        $args = func_get_args();
        foreach ($args as $arg) {
            $this->vars['scripts'][$arg] = $arg;
        }
        return $this;
    }

    public function addStyle () {
        $args = func_get_args();
        foreach ($args as $arg) {
            $this->vars['styles'][$arg] = $arg;
        }
        return $this;
    }

    /**
     * Set Template Var to given Val
     * @return Response
     */
    public function set ($what, $toWhat = null) {
        if (is_object($toWhat)) {
            if (is_a($toWhat, 'Model')) {
                $toWhat = $toWhat->getData();
            } elseif (get_class($toWhat) !== 'stdClass') {
                $toWhat = (array)$toWhat;
            }
        }
        if (is_array($what)) {
            if (is_null($toWhat)) {
                foreach ($what as $k => $v) {
                    $this->vars[$k] = $v;
                }
            } else {
                foreach ($what as $key) {
                    $this->vars[$key] = $toWhat;
                }
            }
        } else {
            $this->vars[$what] = $toWhat;
        }
        return $this;
    }

    /**
     * Get the current template value for the given var
     * @param String $what
     * @param mixed $default What to return if not found
     */
    public function get ($what, $default = null)
    {
        $All = $this->getAllTemplateVars(false);
        if (!array_key_exists($what, $All)) return $default;
        return $All[$what];
    }

    /**
     *
     */
    public function __set($what, $val) {
        if (in_array($what, $this->public)) $this->$what = $val;
        return $val;
    }

    public function appendConent($str) {
        if (!is_string($this->content)) throw new ExceptionBase('attempting append on a non-string');
        $this->content .= $str;
        return $this;
    }

    public function addHeader($header) {
        if (func_num_args() > 1) {
            $this->headers[] = func_get_args();
        } else {
            $this->headers[] = $header;
        }
        return $this;
    }

    /**
     * Make this response a redirect response
     * @param String $location Absolute, optionally fully qualified
     * @param Int $code Redirect status code
     * @return Response
     */
    public function redirectTo($location, $code = null) {
        if (is_array($location)) {
            $this->location = FilterRoutes::buildUrl($location);
        } else {
            $this->location = $location;
        }
        $this->type = self::TYPE_LOCATION;
        if (!is_null($code)) $this->redirectCode = $code;
        return $this;
    }

    /**
     * @param Constant $newType
     * @return Response
     */
    public function cancelRedirect($newType = self::TYPE_HTML) {
        $this->type = $newType;
    }

    protected function getTwigOptions () {
        $opts = array('debug' => DEBUG);
        if (defined('TEMPLATE_CACHE_DIR')) {
            $opts['cache'] = TEMPLATE_CACHE_DIR;
        }
        return $opts;
    }

    public function setMessage ($msg, $bad = false) {
        $k = $bad ? 'f_msg' : 'msg';
        $_SESSION[$k] = array($msg);
    }

    public function addMessage ($msg, $bad = false) {
        if (is_object($msg) && is_a($msg, 'ExceptionBase')) {
            $msg = $msg->getMessage();
            $bad = true;
        }
        if (!empty($msg)) {
            $k = $bad ? 'f_msg' : 'msg';
            $_SESSION[$k][] = $msg;
        }
    }

    public function clearMessages ($bad = null)
    {
        if (is_null($bad) || $bad) {
            $_SESSION['f_msg'] = array();
        }
        if (is_null($bad) || !$bad) {
            $_SESSION['msg'] = array();
        }
    }

    /**
     * @return Twig_Environment
     */
    public function getTwigEnvironment($bypassCache = false)
    {
        if (is_null($this->TwigEnvironment) || $bypassCache) {
            $this->TwigEnvironment = new Twig_Environment(new Twig_Loader_Filesystem($this->tplDirs), $this->getTwigOptions());
            $this->TwigEnvironment->addExtension(new Twig_Extension_Debug());
        }
        return $this->TwigEnvironment;
    }

    public function getBasicTemplateVars()
    {
        return array_merge($this->defaultVars, $this->appVars);
    }

    protected function getAllTemplateVars($consumeMessages = false)
    {
        $msgs = array();
        if ($consumeMessages) {
            $msgs = array('messages' => $_SESSION['msg'], 'errors' => $_SESSION['f_msg']);
            $_SESSION['f_msg'] = $_SESSION['msg'] = array();
        }
        return array_merge($msgs, $this->getBasicTemplateVars(), $this->vars);
    }

    /**
     * @return String
     */
    public function renderFromTemplate($template, $consumeMessages = false)
    {
        try {
            $content = $this->getTwigEnvironment()->render($template, $this->getAllTemplateVars($consumeMessages));
        } catch (Twig_Error $e) {
            if (DEBUG) {
                $content = $e->getMessage();
            } else {
                throw new ExceptionBase($e->getMessage(), 2, $e);
            }
        }
        return $content;
    }

    /**
     *
     * @return void
     */
    public function render() {
        $content = $this->content;
        foreach ($this->headers as $header) {
            if (is_array($header)) {
                header($header[0], $header[1], $header[2]);
            } else {
                header($header);
            }
        }
        if (is_null($this->contentType) && isset(self::$ContentTypes[$this->type])) {
            $this->contentType = self::$ContentTypes[$this->type];
        }
        if (!empty($this->contentType)) {
            header('Content-Type: ' . $this->contentType, true);
        }
        $echoContent = true;
        switch ($this->type) {
            case self::TYPE_LOCATION :
                if (DEBUG) {
                    header('X-Invocation: ' . json_encode($this->invocation));
                }
                header('Location: ' . $this->location, true, $this->redirectCode);
                return;
                break;
            case self::TYPE_TEMPLATE_IN_JSON :
                $array = array('html' => $this->renderFromTemplate($this->template, false));
                UtilsArray::ifKeyAddToThis('js', $this->getAllTemplateVars(), $array);
                UtilsString::forceUTF8($array);
                $content = json_encode($array);
                break;
            case self::TYPE_HTML :
                header('Content-Type: text/html');
                $content = $this->renderFromTemplate($this->template, true);
                break;
            case self::TYPE_JSON :
                if (DEBUG && is_array($content) && PERMIT_AJAX_DEBUG) {
                    $content['_invocation'] = $this->invocation;
                }
                if (is_array($content) && isset($content['html']) && is_object($content['html']) && get_class($content['html']) !== 'stdClass') {
                    $content['html'] = "{$content['html']}";
                }
                UtilsString::forceUTF8($content);
                $content = json_encode($content);
                break;
            case self::TYPE_CSV :
                if (!empty($this->filename)) {
                    header('Content-disposition: attachment; filename="' . $this->filename . '"');
                }
                break;
            case self::TYPE_FILESTREAM :
                $echoContent = false;
                $i = readfile($this->content);
                break;
            case self::TYPE_RAW_ECHO :
                $echoContent = true;
                break;
            case self::TYPE_EMPTY :
                $echoContent = false;
                break;
        }
        if ($echoContent) echo $content;
    }

}

