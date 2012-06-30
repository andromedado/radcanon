<?php

class UtilsArray {
	public $whenNotFound;
	/** @var Array $base */
	protected $base;
	protected static $CompareMethod = '__toString';
	protected static $CompareArguments = array();
	protected static $CompareInvert = false;
	
	public function __construct (array $base, $whenNotFound = NULL) {
		$this->base = $base;
		$this->whenNotFound = $whenNotFound;
	}
	
	public function get ($key, $otherwise = NULL) {
		if (array_key_exists($key, $this->base)) return $this->base[$key];
		if (!is_null($otherwise)) return $otherwise;
		return $this->whenNotFound;
	}
	
	public function __get($key) {
		if (array_key_exists($key, $this->base)) return $this->base[$key];
		return $this->whenNotFound;
	}
	
	public static function ifKeyAddToThis($key, array $tested, array &$recip, $newKey = NULL) {
		if (array_key_exists($key, $tested)) {
			if (is_null($newKey)) $newKey = $key;
			$recip[$newKey] = $tested[$key];
		}
	}
	
	public static function checkEmptiness (array $tested, array $testWith) {
		$empty = false;
		foreach ($testWith as $key) {
			$empty = $empty || empty($tested[$key]);
		}
		return $empty;
	}
	
	protected static function compWith($a, $b) {
		$aR = call_user_func_array(array($a, self::$CompareMethod), self::$CompareArguments);
		$bR = call_user_func_array(array($b, self::$CompareMethod), self::$CompareArguments);
		if ($aR == $bR) return 0;
		$one = self::$CompareInvert ? -1 : 1;
		$negativeOne = self::$CompareInvert ? 1 : -1;
		return $aR > $bR ? $one : $negativeOne;
	}
	
	/**
	 * Sort the array of objects using the given method and arguments
	 * Optionally invert the comparison
	 * @param Array $Os
	 * @param String $Method
	 * @param Array $Arguments
	 * @param Boolean $Invert
	 * @return Array
	 */
	public static function orderWithMethod(array $Os, $Method, array $Arguments = array(), $Invert = false) {
		self::$CompareMethod = $Method;
		self::$CompareArguments = $Arguments;
		self::$CompareInvert = $Invert;
		uasort($Os, array(__CLASS__, 'compWith'));
		return $Os;
	}
	
	/**
	 * Remove anything in the given array that's not on the whitelist
	 * @param Array $raw
	 * @param Array $whiteList
	 * @param Boolean $addKeys Should the final array have all keys found in the whitelist?
	 * @return Array
	 */
	public static function filterWithWhiteList(array $raw, array $whiteList, $addKeys = NULL) {
		$Fin = array();
		foreach ($whiteList as $key) {
			if (array_key_exists($key, $raw) && ($addKeys !== false || isset($raw[$key]))) {
				$Fin[$key] = $raw[$key];
			} elseif ($addKeys === true) {
				$Fin[$key] = NULL;
			}
		}
		return $Fin;
	}
	
	/**
	 * Calling the given function on each of the items in the array,
	 * is at least one of the responses true?
	 * @param Array $A
	 * @param String|Array $function
	 * @param Boolean $strict
	 * @return Boolean
	 */
	public static function atLeastOneTrue(array $A, $function, $strict = true) {
		$atLeastOne = false;
		foreach ($A as $I) {
			if ($strict) {
				if (call_user_func($function, $I) === true) {
					$atLeastOne = true;
					break;
				}
			} else {
				if (call_user_func($function, $I)) {
					$atLeastOne = true;
					break;
				}
			}
		}
		return $atLeastOne;
	}
	
	/**
	 * Calling the given method on each of the objects in the array,
	 * is at least one of them true?
	 * @param Array $Os
	 * @param String $method
	 * @param Boolean $strict
	 * @param Boolean $invert Invert the test?
	 * @return Boolean
	 */
	public static function atLeastOneObjectTrue(array $Os, $method, array $arguments = array(), $strict = true, $invert = false) {
		$atLeastOne = false;
		foreach ($Os as $O) {
			if ($strict) {
				if ((!$invert && call_user_func_array(array($O, $method), $arguments) === true) || 
					($invert && call_user_func_array(array($O, $method), $arguments) === false)) {
					$atLeastOne = true;
					break;
				}
			} else {
				if ((!$invert && call_user_func_array(array($O, $method), $arguments)) || 
					($invert && !call_user_func_array(array($O, $method), $arguments))) {
					$atLeastOne = true;
					break;
				}
			}
		}
		return $atLeastOne;
	}
	
	/**
	 * Redo the array so that the array key for each Object is the result of the given method
	 * @param Array $Os
	 * @param String $Method
	 * @param Array $Arguments
	 * @return Array
	 */
	public static function redoKeysWithMethod(array $Os, $Method, array $Arguments = array()) {
		$Fin = array();
		foreach ($Os as $O) {
			$k = call_user_func_array(array($O, $Method), $Arguments);
			$Fin[$k] = $O;
		}
		return $Fin;
	}
	
	/**
	 * Filters the given array of objects by the given Method
	 * If the returned value from the method call is truthy,
	 * the Object Makes it onto the final array
	 * You can optionally pass arguements to the method
	 * 
	 * @param Array $Os
	 * @param String $Method
	 * @param Array $Arguments
	 * @param Boolean $invert Invert the test?
	 * @return Array
	 */
	public static function filterWithMethod(array $Os, $Method, array $Arguments = array(), $invert = false) {
		$Fin = array();
		foreach ($Os as $oid => $O) {
			$test = call_user_func_array(array($O, $Method), $Arguments);
			if (($test && !$invert) || (!$test && $invert)) {
				$Fin[$oid] = $O;
			}
		}
		return $Fin;
	}
	
	/**
	 * Filters the given array of objects by the given Methods
	 * If the returned value from the method calls are all truthy,
	 * the Object Makes it onto the final array
	 * You must specify an array [empty ok] of arguments for each method
	 * 
	 * @param Array $Os
	 * @param Array $Methods
	 * @return Array
	 */
	public static function filterWithMethods(array $Os, array $Methods) {
		$Fin = array();
		foreach ($Os as $oid => $O) {
			$inc = true;
			foreach ($Methods as $Method => $Arguments) {
				if (method_exists($O, $Method)) {
					$inc = $inc && call_user_func_array(array($O, $Method), $Arguments);
				}
			}
			if ($inc) $Fin[$oid] = $O;
		}
		return $Fin;
	}
	
	/**
	 * Given an array, concatenates all values recursively
	 * @param array $array
	 * @return string
	 */
	public static function concat(array $array){
		$str='';
		foreach($array as $v){
			if(is_array($v)) $v=self::concat($v);
			$str.=$v;
		}
		return $str;
	}
	
	/**
	 * Builds an Html `<select>` from the given data
	 * @param array $data The data to build the `<select>` from
	 * @param string|int $selected The option to preselect (Strict Comparison is used)
	 * @param string $name name attribute for the `<select>`
	 * @param string $id id attribute for the `<select>`
	 * @return Html The constructed `<select>`
	 */
	public static function buildSelect(array $data,$selected=false,$name='',$id=''){
		$s = HtmlE::n('select')->name($name)->id($id);
		foreach ($data as $v=>$I) {
			$s->a(HtmlE::n('option')->value($v)->a($I)->selected($v===$selected));
		}
		return $s;
	}
	
	/**
	 * Calls a Class's method using each [set of] arguments in the provided array
	 * @param array $ArgsArr Either an array of single paramters to pass to the method, or an array of arrays of parameters to pass to the method
	 * @param string|Object $Class Either a Class name or an instance
	 * @param string $Method Method to be called on the $Class
	 * @return array Array of the results from the calls
	 */
	public static function callWithEach(array $ArgsArr, $Class, $Method){
		$r=array();
		foreach($ArgsArr as $k=>$Arguments){
			$r[$k] = NULL;
			if(is_callable(array($Class, $Method))){
				if(is_array($Arguments)){
					$r[$k]=call_user_func_array(array($Class,$Method), $Arguments);
				}else{
					$r[$k]=call_user_func(array($Class,$Method), $Arguments);
				}
			}
		}
		return $r;
	}
	
	/**
	 * Given an array of Object Instances or Classes, calls the given method with the given arguments on each
	 * @param array $Os Array of Object Instances or Class Names
	 * @param string $Method Method to be called
	 * @param string|array $Arguments either one argument or an array of arguments to be passed to the method
	 * @return array Array of the results of the calls
	 */
	public static function callOnAll(array $Os, $Method, $Arguments = array()){
		$r = array();
		foreach($Os as $k => $Class){
			$r[$k] = NULL;
			if(is_callable(array($Class, $Method))){
				if(is_array($Arguments)){
					$r[$k] = call_user_func_array(array($Class, $Method), $Arguments);
				}else{
					$r[$k] = call_user_func(array($Class, $Method), $Arguments);
				}
			}
		}
		return $r;
	}
	
	/**
	 * Given an array of Object Instances or Classes
	 * gets one unified array of result arrays of calling the given method with the given arguments on each
	 * @param array $Os Array of Object Instances or Class Names
	 * @param string $Method Method to be called
	 * @param string|array $Arguments either one argument or an array of arguments to be passed to the method
	 * @return array Array of the results of the calls
	 */
	public static function mergeCallOnAll(array $Os, $Method, array $Arguments = array()) {
		$Oss = self::callOnAll($Os, $Method, $Arguments);
//		vdump($Oss);
		$Os = array();
		foreach ($Oss as $OS) {
			foreach ($OS as $O) {
				$id = $O->getID();
				if (!isset($Os[$id])) $Os[$id] = $O;
			}
		}
		return $Os;
	}
	
	/**
	 * Given an array of Object Instances or Classes
	 * gets the sum of calling the given method with the given arguments on each
	 * @param array $Os Array of Object Instances or Class Names
	 * @param string $Method Method to be called
	 * @param string|array $Arguments either one argument or an array of arguments to be passed to the method
	 * @return Float
	 */
	public static function sumCallOnAll(array $Os, $Method, array $Arguments = array()) {
		return array_sum(self::callOnAll($Os, $Method, $Arguments));
	}
	
	/**
	 * Sum the results of calling the given Class's method
	 * using each [set of] arguments in the provided array
	 * @param array $ArgsArr Either an array of single paramters to pass to the method, or an array of arrays of parameters to pass to the method
	 * @param string|Object $Class Either a Class name or an instance
	 * @param string $Method Method to be called on the $Class
	 * @return Float
	 */
	public static function sumCallWithEach(array $ArgsArr, $Class, $Method){
		return array_sum(self::callWithEach($ArgsArr, $Class, $Method));
	}
	
}

?>