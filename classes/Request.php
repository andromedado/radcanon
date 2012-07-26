<?php

class Request {
	protected $server;
	protected $iniServer;
	protected $get;
	protected $iniGet;
	protected $post;
	protected $iniPost;
	protected $cookie;
	protected $iniCookie;
	
	protected $Accessable = array('get', 'server', 'post', 'cookie');
	protected $Abstracted = array('get', 'server', 'post', 'cookie');
	
	public function __construct(array $_server, array $_get, array $_post, array $_cookie) {
		$this->iniServer = $this->server = $_server;
		$this->iniGet = $this->get = $_get;
		$this->iniPost = $this->post = $_post;
		$this->iniCookie = $this->cookie = $_cookie;
	}
	
	public function __call($func, $args) {
		if (in_array($func, $this->Abstracted)) {
			$key = $default = $cast = null;
			switch (true) {
				case count($args) > 2 :
					$cast = $args[2];
				case count($args) > 1 :
					$default = $args[1];
				case !empty($args) :
					$key = $args[0];
			}
			if (is_null($key)) {
				return $this->$func;
			}
			return $this->abstractedGet($key, $this->$func, $default, $cast);
		}
		return parent::__call($func, $args);
	}
	
	public function postFieldEmpty () {
		$args = func_get_args();
		return UtilsArray::checkEmptiness($this->post, $args);
	}
	
	public function getFieldEmpty () {
		$args = func_get_args();
		return UtilsArray::checkEmptiness($this->get, $args);
	}
	
	public function serverFieldEmpty () {
		$args = func_get_args();
		return UtilsArray::checkEmptiness($this->server, $args);
	}
	
	public function __get($var) {
		if (in_array($var, $this->Accessable)) return $this->$var;
		return NULL;
	}
	
	public function getGET() {
		return $this->get;
	}
	
	public function deepGet() {
		$args = func_get_args();
		$default = array_shift($args);
		return $this->abstractedDeepGet($args, $this->get, $default);
	}
	
	public function deepPost() {
		$args = func_get_args();
		$default = array_shift($args);
		return $this->abstractedDeepGet($args, $this->post, $default);
	}
	
	public function abstractedDeepGet(array $keys, array $array, $default = NULL) {
		$resp = $default;
		$curArr = $array;
		foreach ($keys as $f) {
			if (!is_array($curArr) || !isset($curArr[$f])) return $default;
			$curArr = $curArr[$f];
		}
		return $curArr;
	}
	
	public function abstractedGet($key, array $array, $default = NULL, $cast = NULL) {
		if (strpos($key, '[') !== false) {
			$results = array();
			if (preg_match('/^([A-Z\d_-]+)\[([A-Z\d_-]+)\](.*)$/i', $key, $results) && 
				isset($array[$results[1]]) && 
				is_array($array[$results[1]])) {
				return $this->abstractedGet($results[2] . $results[3], $array[$results[1]], $default);
			}
		}
		$value = isset($array[$key]) ? $array[$key] : $default;
		if (!is_null($cast)) {
			switch ($cast) {
				case 'array' :
					$value = (array)$value;
					break;
				case 'int' :
				case 'integer' :
					$value = (int)$value;
					break;
				case 'float' :
				case 'double' :
				case 'real' :
					$value = (float)$value;
					break;
				case 'bool' :
				case 'boolean' :
					$value = (bool)$value;
					break;
				case 'string' :
					$value = (string)$value;
					break;
				case 'object' :
					$value = (object)$value;
					break;
			}
		}
		return $value;
	}
	
	public function getGETVal($key, $default = NULL) {
		return $this->abstractedGet($key, $this->get, $default);
	}
	
	public function getIniGET() {
		return $this->iniGet;
	}
	
	public function getPOST() {
		return $this->post;
	}
	
	public function getPOSTVal($key, $default = NULL) {
		return $this->abstractedGet($key, $this->post, $default);
	}
	
	public function isGetEmpty($key = NULL) {
		if (is_null($key)) return empty($this->get);
		return empty($this->get[$key]);
	}
	
	public function isPostEmpty($key = NULL) {
		if (is_null($key)) return empty($this->post);
		return empty($this->post[$key]);
	}
	
	public function getIniPOST() {
		return $this->iniPost;
	}
	
	public function getSERVERVal($key, $default = NULL) {
		return $this->abstractedGet($key, $this->server, $default);
	}
	
	/**
	 * Get the Request URI (witout query string)
	 * @return String
	 */
	public function getURI() {
		if (USE_HTACCESS) {
			return preg_replace('/\?.*$/', '', $this->getSERVERVal('REQUEST_URI', ''));
		}
		$uri = preg_replace('#^' . preg_quote(APP_SUB_DIR . '/index.php?', '#') . '#', '', $this->getSERVERVal('REQUEST_URI', ''));
		if (strpos($uri, '&') !== false) {
			$uri = preg_replace('#^([^&]+)&.*#', '$1', $uri);
		}
		return $uri;
	}
	
	public function getIniURI() {
		return empty($this->iniServer['REQUEST_URI']) ? '' : $this->iniServer['REQUEST_URI'];
	}
	
	public function setURI($uri) {
		if (is_array($uri)) {
			$uri = FilterRoutes::buildUrl($uri);
		}
		return $this->server['REQUEST_URI'] = $uri;
	}
	
	/**
	 * Is this a POST request?
	 * @return bool
	 */
	public function isPost() {
		return !empty($this->post) || (isset($this->server['REQUEST_METHOD']) && $this->server['REQUEST_METHOD'] === 'POST');
	}
	
	/**
	 * Is this an AJAX request?
	 * @return bool
	 */
	public function isAjax() {
		return $this->get('requestType', '') === 'ajax' || (isset($_SERVER['HTTP_REQBY']) && strtolower($_SERVER['HTTP_REQBY']) === 'ajax') || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
	}
	
}

?>