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
	
	public function __construct(array $_server, array $_get, array $_post, array $_cookie) {
		$this->iniServer = $this->server = $_server;
		$this->iniGet = $this->get = $_get;
		$this->iniPost = $this->post = $_post;
		$this->iniCookie = $this->cookie = $_cookie;
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
	
	public function abstractedGet($key, array $array, $default = NULL) {
		if (strpos($key, '[') !== false) {
			$results = array();
			if (preg_match('/^([A-Z\d_-]+)\[([A-Z\d_-]+)\](.*)$/i', $key, $results) && 
				isset($array[$results[1]]) && 
				is_array($array[$results[1]])) {
				return $this->abstractedGet($results[2] . $results[3], $array[$results[1]], $default);
			}
		}
		return isset($array[$key]) ? $array[$key] : $default;
	}
	
	public function getGETVal($key, $default = NULL) {
		return $this->abstractedGet($key, $this->get, $default);
	}
	
	public function get($key = NULL, $default = NULL) {
		if (is_null($key)) return $this->get;
		return $this->abstractedGet($key, $this->get, $default);
	}
	
	public function cookie($key, $default = NULL) {
		return $this->abstractedGet($key, $this->cookie, $default);
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
	
	public function post($key = NULL, $default = NULL) {
		if (is_null($key)) return $this->post;
		return $this->abstractedGet($key, $this->post, $default);
	}
	
	public function server($key = NULL, $default = NULL) {
		if (is_null($key)) return $this->server;
		return $this->abstractedGet($key, $this->server, $default);
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
		return preg_replace('/\?.*$/', '', $this->getSERVERVal('REQUEST_URI', ''));
	}
	
	public function getIniURI() {
		return empty($this->iniServer['REQUEST_URI']) ? '' : $this->iniServer['REQUEST_URI'];
	}
	
	public function setURI($uri) {
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