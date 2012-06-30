<?php

/**
 * Response Object
 *
 * @package RAD-Canon
 * @author Shad Downey
 */
class Response {
	const TYPE_HTML = 0;
	const TYPE_JSON = 1;
	const TYPE_XML = 2;
	const TYPE_LOCATION = 3;
	const TYPE_CSV = 4;
	const TYPE_FILESTREAM = 5;
	
	/**@var Request $request */
	protected $request = NULL;
	protected $type = 0;
	protected $error = false;
	protected $headers = array();
	/** @var Exception $exception */
	protected $exception = NULL;
	protected $location = NULL;
	protected $redirectCode = 302;
	protected $contentType = NULL;
	protected $content = NULL;
	protected $invocation = NULL;
	protected $filename = NULL;
	protected $template = 'base.html.twig';
	protected $defaultVars = array(
		'styles' => array(),
		'scripts' => array(),
		'title' => ' - RADCANON - ',
		'content' => '',
		'metas' => '',
		'baseHref' => '',
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
		NULL,
		'text/csv',
	);
	
	public function __construct(Request $req) {
		$this->request = $req;
		$this->set('currentUri', $this->request->getIniURI());
		if (!isset($_SESSION['msg'])) $_SESSION['msg'] = array();
		$this->load();
	}
	
	protected function load() {
		
	}
	
	public function forceSSL () {
		if ($this->request->server('SERVER_PORT') !== '443') {
			$this->redirectTo('https://' . SITE_HOST . $this->request->getIniURI());
			$this->render();
			exit;
		}
	}
	
	public function setException(Exception $e) {
		$this->exception = $e;
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
		return NULL;
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
	public function set ($what, $toWhat) {
		if (is_object($toWhat)) {
			if (is_a($toWhat, 'Model')) {
				$toWhat = $toWhat->getData();
			} elseif (get_class($toWhat) !== 'stdClass') {
				$toWhat = (array)$toWhat;
			}
		}
		$this->vars[$what] = $toWhat;
		return $this;
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
	public function redirectTo($location, $code = NULL) {
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
	
	public function setMessage ($msg) {
		$_SESSION['msg'] = array($msg);
	}
	
	public function addMessage ($msg) {
		$_SESSION['msg'][] = $msg;
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
			case self::TYPE_HTML :
				try {
					$twigLoader = new Twig_Loader_Filesystem(array(APP_TEMPLATES_DIR, RADCANON_TEMPLATES_DIR));
					$twigEnv = new Twig_Environment($twigLoader, $this->getTwigOptions());
					$twigEnv->addExtension(new Twig_Extension_Debug());
					$this->set('messages', $_SESSION['msg']);
					$content = $twigEnv->render($this->template, array_merge($this->defaultVars, $this->appVars, $this->vars));
					$_SESSION['msg'] = array();
				} catch (Twig_Error $e) {
					if (DEBUG) {
						$content = $e->getMessage();
					} else {
						throw new ExceptionBase($e->getMessage(), 2, $e);
					}
				}
			break;
			case self::TYPE_JSON :
				if (DEBUG && is_array($content)) {
					$content['_invocation'] = $this->invocation;
				}
				if (is_array($content) && isset($content['html']) && is_object($content['html']) && get_class($content['html']) !== 'stdClass') {
					$content['html'] = "{$content['html']}";
				}
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
		}
		if ($echoContent) echo $content;
	}
	
}

?>