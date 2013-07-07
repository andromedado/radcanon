<?php

/**
 * RAD-Canon Request Abstraction
 * Primarily wraps the super-globals for more convenient access
 * @author Shad Downey
 * @method mixed get()
 * @method mixed post()
 * @method mixed server()
 * @method mixed cookie()
 * @method mixed files()
 */
class Request
{
    protected $server;
    protected $iniServer;
    protected $get;
    protected $iniGet;
    protected $post;
    protected $iniPost;
    protected $files;
    protected $iniFiles;
    protected $cookie;
    protected $iniCookie;

    protected $Accessable = array('get', 'server', 'post', 'cookie', 'files');
    protected $Abstracted = array('get', 'server', 'post', 'cookie', 'files');

    protected static $Info = array();

    public function __construct(
        array $_server = null,
        array $_get = null,
        array $_post = null,
        array $_cookie = null,
        array $_files = null
    ) {
        if (is_null($_server)) {
            $_server = $_SERVER;
        }
        $this->iniServer = $this->server = $_server;
        if (is_null($_get)) {
            $_get = $_GET;
        }
        $this->iniGet = $this->get = $_get;
        if (is_null($_post)) {
            $_post = $_POST;
        }
        $this->iniPost = $this->post = $_post;
        if (is_null($_cookie)) {
            $_cookie = $_COOKIE;
        }
        $this->iniCookie = $this->cookie = $_cookie;
        if (is_null($_files)) {
            $_files = $_FILES;
        }
        $this->iniFiles = $this->files = $_files;
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
        if (count($args) === 2 && preg_match('/^set_([A-Za-z]+)$/', $func, $m) && in_array($m[1], $this->Abstracted)) {
            return $this->{$m[1]}[$args[0]] = $args[1];
        }
        if (DEBUG || LOTS_OF_LOGS) {
            ModelLog::mkLog('failed method call: ' . $func . ' : ' . json_encode($args), '0', __FILE__, __LINE__);
        }
        return null;//parent::__call($func, $args);
    }

    public function amalgamatePostArrays()
    {
        $arrays = func_get_args();
        if (count($arrays) === 1 && is_array(current($arrays))) $arrays = current($arrays);
        return UtilsArray::amalgamateArrays($this->post, $arrays);
    }

    public function amalgamateGetArrays()
    {
        $arrays = func_get_args();
        if (count($arrays) === 1 && is_array(current($arrays))) $arrays = current($arrays);
        return UtilsArray::amalgamateArrays($this->get, $arrays);
    }

    public function unsetKeys($keys, &$array)
    {
        foreach ($keys as $key) {
            unset($array[$key]);
        }
    }

    public function unsetPostKeys()
    {
        $keys = func_get_args();
        $this->unsetKeys($keys, $this->post);
    }

    public function unsetGetKeys()
    {
        $keys = func_get_args();
        $this->unsetKeys($keys, $this->get);
    }

    public function unsetCookieKeys()
    {
        $keys = func_get_args();
        $this->unsetKeys($keys, $this->cookie);
    }

    public function unsetServerKeys()
    {
        $keys = func_get_args();
        $this->unsetKeys($keys, $this->server);
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
                case 'UtilsArray' :
                    $value = new UtilsArray((array)$value, $default);
                    break;
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

    /**
     * Set some information
     * This is as close to global scope as I'm currently comfortable with
     * @param String $key
     * @param mixed $val
     * @return void
     */
    public static function setInfo($key, $val)
    {
        static::$Info[strval($key)] = $val;
    }

    /**
     * Get some information
     * @param String $key
     * @param mixed $default
     * @return mixed
     */
    public static function getInfo($key, $default = null)
    {
        $key = strval($key);
        if (!array_key_exists($key, static::$Info)) return $default;
        return static::$Info[$key];
    }

    /**
     * Get all information currently set
     * @return Array
     */
    public static function getAllInfo()
    {
        return static::$Info;
    }

}

