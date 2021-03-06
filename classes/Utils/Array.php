<?php

class UtilsArray
{
    const UNIQUE_STRING = 'this shall not be used';
    public $whenNotFound;
    /** @var Array $base */
    protected $base;
    protected $whiteList = null;
    protected static $CompareIndex = null;
    protected static $CompartIndexInvert = false;
    protected static $CompareMethod = '__toString';
    protected static $CompareArguments = array();
    protected static $CompareInvert = false;

    public function __construct (array $base = array(), $whenNotFound = NULL) {
        $this->base = $base;
        $this->whenNotFound = $whenNotFound;
    }

    public function get ($key = null, $overridingOtherwise = self::UNIQUE_STRING) {
        if (is_null($key)) return $this->base;
        if (array_key_exists($key, $this->base)) return $this->base[$key];
        if ($overridingOtherwise !== self::UNIQUE_STRING) return $overridingOtherwise;
        return $this->whenNotFound;
    }

    public function setWhitelist (array $whiteList)
    {
        $this->whiteList = $whiteList;
        return $this;
    }

    public function __set($key, $val)
    {
        if (!is_null($this->whiteList) && !in_array($key, $this->whiteList)) return null;
        return $this->base[$key] = $val;
    }

    public function __get($key) {
        if (array_key_exists($key, $this->base)) return $this->base[$key];
        return $this->whenNotFound;
    }

    public static function getValuesForTheseKeys(array $keys, array $pullFrom, $useNullIfNotFound = true) {
        $fin = array();
        foreach ($keys as $key) {
            if (array_key_exists($key, $pullFrom)) {
                $fin[] = $pullFrom[$key];
            } elseif ($useNullIfNotFound) {
                $fin[] = NULL;
            }
        }
        return $fin;
    }

    public static function ifKeyAddToThis($key, array $tested, array &$recip, $newKey = NULL) {
        if (array_key_exists($key, $tested)) {
            if (is_null($newKey)) $newKey = $key;
            $recip[$newKey] = $tested[$key];
        }
    }

    public static function useModelIdForKey (array $Models) {
        return self::redoKeysWithMethod($Models, 'getID');
    }

    /**
     * Given any combination/qty of Models and Arrays of Models,
     * return an array of unique models
     * Models presumed to have mutually exclusive ids
     * @param mixed
     * @return Array
     */
    public static function getUniqueModels ()
    {
        $args = func_get_args();
        $Models = array();
        foreach ($args as $arg) {
            if (!is_array($arg)) {
                if (!is_a($arg, 'Model')) throw new ExceptionBase('Invalid argument, only models and arrays of models permitted');
                $arg = array($arg);
            }
            $Models = array_merge($Models, $arg);
        }
        return self::useModelIdForKey($Models);
    }

    public static function checkEmptiness (array $tested, array $testWith)
    {
        $empty = false;
        foreach ($testWith as $key) {
            $empty = $empty || empty($tested[$key]);
        }
        return $empty;
    }

    protected static function compIndex($a, $b)
    {
        $A = isset($a[self::$CompareIndex]) ? $a[self::$CompareIndex] : null;
        $B = isset($b[self::$CompareIndex]) ? $b[self::$CompareIndex] : null;
        $one = self::$CompartIndexInvert ? -1 : 1;
        return strcmp(strval($A), strval($B)) * $one;
    }

    /**
     * Sort the given arrays by the value of the given index in each
     * @param Array $Arrays
     * @param String $index
     * @param Boolean $invert
     * @return Array
     */
    public static function sortArraysByIndex(array $Arrays, $index, $invert = false)
    {
        self::$CompareIndex = $index;
        self::$CompartIndexInvert = !!$invert;
        uasort($Arrays, array(__CLASS__, 'compIndex'));
        return $Arrays;
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
     * @deprecated in favor of `sortWithMethod`
     * @param Array $Os
     * @param String $Method
     * @param Array $Arguments
     * @param Boolean $Invert
     * @return Array
     */
    public static function orderWithMethod(array $Os, $Method, array $Arguments = array(), $Invert = false) {
        return self::sortWithMethod($Os, $Method, $Arguments, $Invert);
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
    public static function sortWithMethod(
        array $Os,
        $Method,
        array $Arguments = array(),
        $Invert = false
    ) {
        self::$CompareMethod = $Method;
        self::$CompareArguments = $Arguments;
        self::$CompareInvert = $Invert;
        uasort($Os, array(__CLASS__, 'compWith'));
        return $Os;
    }

    /**
     * Use the given method to split the array into two groups by boolean response,
     * and then recombine the resultant groups
     * @param Array $Os
     * @param String $Method
     * @param Array $Arguments
     * @param Boolean $Invert
     * @return Array
     */
    public static function orderWithBool (array $Os, $Method, array $Arguments = array(), $Invert = false) {
        return array_merge(self::filterWithMethod($Os, $Method, $Arguments, $Invert),
            self::filterWithMethod($Os, $Method, $Arguments, !$Invert));
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
     * Build a new array using the specified methods for values and keys
     * @param Array $Os
     * @param String $ValueMethod
     * @param Array $ValueArguments
     * @param String $KeyMethod
     * @param Array $KeyArguments
     * @return Array
     */
    public static function buildWithMethods(
        array $Os,
        $ValueMethod,
        array $ValueArguments = array(),
        $KeyMethod = NULL,
        array $KeyArguments = array()
    ) {
        $fin = array();
        if (is_null($KeyMethod)) {
            foreach ($Os as $O) {
                $fin[] = call_user_func_array(array($O, $ValueMethod), $ValueArguments);
            }
        } else {
            foreach ($Os as $O) {
                $k = call_user_func_array(array($O, $KeyMethod), $KeyArguments);
                $fin[$k] = call_user_func_array(array($O, $ValueMethod), $ValueArguments);
            }
        }
        return $fin;
    }

    /**
     * Filter the given single-dimension array of strings with the given Regular Expression
     * Optionally invert the regexp test
     * @param Array $array
     * @param String $regexp
     * @param Boolean $invert
     * @return Array
     */
    public static function filterWithRegexp (array $array, $regexp, $invert = false)
    {
        $fin = array();
        foreach ($array as $key => $value) {
            if ((bool)preg_match($regexp, strval($value)) !== (bool)$invert) {
                $fin[$key] = $value;
            }
        }
        return $fin;
    }

    /**
     * Filters the given array of objects by the given Method
     * If the returned value from the method call is truthy,
     * the Object Makes it onto the final array
     * You can optionally pass arguements to the method,
     * and/or invert the logic of the test
     *
     * @param Array $Os
     * @param String $Method
     * @param Array $Arguments
     * @param Boolean $invert Invert the test?
     * @return Array
     */
    public static function filterWithMethod(
        array $Os,
        $Method,
        array $Arguments = array(),
        $invert = false
    ) {
        $Fin = array();
        foreach ($Os as $oid => $O) {
            $test = call_user_func_array(array($O, $Method), $Arguments);
            if (!!$test === !$invert) {
                $Fin[$oid] = $O;
            }
        }
        return $Fin;
    }

    /**
     * Bifurcates the given array of objects by the given Method
     * If the returned value from the method call is truthy,
     * the Object Makes it onto the first array returned.
     * You can optionally pass arguements to the method,
     * and/or invert the logic of the test
     *
     * @param Array $Os
     * @param String $Method
     * @param Array $Arguments
     * @param Boolean $invert Invert the test?
     * @return Array
     */
    public static function bifurcateWithMethod(
        array $Os,
        $Method,
        array $Arguments = array(),
        $invert = false
    ) {
        $first = $second = array();
        foreach ($Os as $oid => $O) {
            $test = call_user_func_array(array($O, $Method), $Arguments);
            if (!!$test === !$invert) {
                $first[$oid] = $O;
            } else {
                $second[$oid] = $O;
            }
        }
        return array($first, $second);
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
    public static function concat(array $array)
    {
        $str = '';
        foreach ($array as $v) {
            if (is_array($v)) $v = self::concat($v);
            $str .= $v;
        }
        return $str;
    }

    /**
     * Builds an Html `<select>` from the given data
     * @deprecated
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
        ModelLog::mkLog('deprecated function call: ' . __METHOD__, 'deprecated', '1', __FILE__, __LINE__);
        return $s;
    }

    /**
     * Calls a Class's method using each [set of] arguments in the provided array
     * @param array $ArgsArr Either an array of single paramters to pass to the method, or an array of arrays of parameters to pass to the method
     * @param string|Object $Class Either a Class name or an instance
     * @param string $Method Method to be called on the $Class
     * @return array Array of the results from the calls
     */
    public static function callWithEach(array $ArgsArr, $Class, $Method)
    {
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
    public static function callOnAll(array $Os, $Method, $Arguments = array())
    {
        $r = array();
        $log = null;
        foreach ($Os as $k => $Class) {
            $r[$k] = NULL;
            if (is_callable(array($Class, $Method))) {
                if (is_array($Arguments)) {
                    $r[$k] = call_user_func_array(array($Class, $Method), $Arguments);
                } else {
                    $r[$k] = call_user_func(array($Class, $Method), $Arguments);
                    //Ergh... I don't like this overloading...
                    //The idea was that you could pass a non-array wrapped single argument
                    //to the method, but if that first parameter was intended to be an array,
                    //it then needs to be wrapped in an array~ seems inconsistent
                    $log = 'UtilsArray::callOnAll called with a non-array third parameter. This will probably be deprecated at some point: ';
                    $log .= is_object($Class) ? get_class($Class) : $Class;
                    $log .= ' ' . $Method . ' ' . json_encode($Arguments);
                }
            }
        }
        if (!is_null($log)) {
            ModelLog::mkLog($log, 'deprecated', '1', __FILE__, __LINE__);
        }
        return $r;
    }

    /**
     * Similar to UtilsArray::callOnAll except more strict, and that the returned value
     * from the method call MUST be a Model, and they are aggregated, and duplicates
     * ignored. (returned array's keys are used to this effect)
     * @param Array $Os
     * @param String $Method
     * @param Array $Arguments
     * @return Array
     */
    public static function getDistinctReturnedModels (
        array $Os,
        $Method,
        array $Arguments = array()
    ) {
        $r = array();
        foreach ($Os as $Class) {
            if (!is_callable(array($Class, $Method))) {
                throw new ExceptionBase('Can\'t call "' . $Method . '" on given class');
            }
            $Return = call_user_func_array(array($Class, $Method), $Arguments);
            self::addModelToArrayIfNotAlreadyThere($Return, $r);
        }
        return $r;
    }

    /**
     * Similar to UtilsArray::callOnAll except more strict,
     * and they are aggregated, and duplicate return values
     * are ignored. (returned array's keys are used to this effect)
     * @param Array $Os
     * @param String $Method
     * @param Array $Arguments
     * @param Boolean $plainReturn Return a numerically indexed array (starting at 0)
     * @return Array
     */
    public static function getDistinctReturnedValues(
        array $Os,
        $Method,
        array $Arguments = array(),
        $plainReturn = false
    ) {
        $r = array();
        foreach ($Os as $Class) {
            if (!is_callable(array($Class, $Method))) {
                throw new ExceptionBase('Can\'t call "' . $Method . '" on given class');
            }
            $Return = call_user_func_array(array($Class, $Method), $Arguments);
            $r[$Return] = $Return;
        }
        if ($plainReturn) {
            $r = array_values($r);
        }
        return $r;
    }

    protected static function addModelToArrayIfNotAlreadyThere ($Model, array &$array)
    {
        if (is_array($Model)) {
            foreach ($Model as $model) {
                self::addModelToArrayIfNotAlreadyThere($model, $array);
            }
            return;
        }
        if (!is_a($Model, 'Model')) {
            throw new ExceptionBase('Not A Model! : ' . $Model);
        }
        $array[get_class($Model) . $Model->id] = $Model;
    }

    /**
     * @param array $Os
     * @param $property
     * @param bool $preserveKeys
     * @return array
     */
    public static function pluckPropertyFromObjects(array $Os, $property, $preserveKeys = true)
    {
        $r = array();
        foreach ($Os as $key => $O) {
            $r[$key] = $O->$property;
        }
        if (!$preserveKeys) {
            $r = array_values($r);
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
    public static function mergeCallOnAll(array $Os, $Method, array $Arguments = array())
    {
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
    public static function sumCallOnAll(array $Os, $Method, array $Arguments = array())
    {
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
    public static function sumCallWithEach(array $ArgsArr, $Class, $Method)
    {
        return array_sum(self::callWithEach($ArgsArr, $Class, $Method));
    }

    /**
     * Given an Array of independent arrays with similar/same indices, return an array
     * with just the shared indices and the array values associated with those
     * indices collapsed together. e.g.
     * param1 - ['hat' => ['baseball-hat', 'top-hat'],
     * 				'gloves' => ['catchers-mit', 'silk-gloves']]
     * returns - [['hat' => 'baseball-hat', 'gloves' => 'cathers-mit'],
     * 				['hat' => 'top-hat', 'gloves' => 'silk-gloves']]
     * @param Array $baseArray
     * @return Array
     */
    public static function autoAmalgamateArrays(array $Arrays)
    {
        return self::amalgamateArrays($Arrays, array_keys($Arrays));
    }

    /**
     * Given an Array of independent arrays with similar/same indices, return an array
     * with just the shared indices and the array values associated with those
     * indices collapsed together. e.g.
     * param1 - ['hat' => ['baseball-hat', 'top-hat'],
     * 				'gloves' => ['catchers-mit', 'silk-gloves']]
     * param2 - ['hat', 'gloves']
     * returns - [['hat' => 'baseball-hat', 'gloves' => 'cathers-mit'],
     * 				['hat' => 'top-hat', 'gloves' => 'silk-gloves']]
     * @param Array $baseArray
     * @param Array $arraysToAmalgamate
     * @return Array
     */
    public static function amalgamateArrays(array $baseArray, array $arraysToAmalgamate)
    {
        $amalgam = array();
        $key = current($arraysToAmalgamate);
        while ($key !== false && (!array_key_exists($key, $baseArray) || !is_array($baseArray[$key]))) {
            $key = next($arraysToAmalgamate);
        }
        reset($arraysToAmalgamate);
        if ($key !== false) {
            foreach ($baseArray[$key] as $index => $val) {
                $elem = array();
                foreach ($arraysToAmalgamate as $array) {
                    if (!array_key_exists($array, $baseArray) || !is_array($baseArray[$array]) || !array_key_exists($index, $baseArray[$array])) {
                        $elem[$array] = null;
                    } else {
                        $elem[$array] = $baseArray[$array][$index];
                    }
                }
                $amalgam[$index] = $elem;
            }
        }
        return $amalgam;
    }

    /**
     * Using the given array, concat all values with the
     * given normalJoin, but the final two elements will
     * be joined with a special string
     * e.g. Red`, `Blue`, `Green` and `Yellow
     * @param Array $Array to be joined
     * @param String $normalJoin
     * @param String $finalJoin
     * @return String
     */
    public static function implodeWithFinalDifferent(array $Array, $normalJoin = ', ', $finalJoin = ' and ')
    {
        if (count($Array) > 1) {
            $fin = array_pop($Array);
            $Array = array(implode($normalJoin, $Array), $fin);
        }
        return implode($finalJoin, $Array);
    }

    /**
     * With all of the given models, compose an array whose indexes are
     * the distinct returned values from the given method with the given arguments,
     * and whose values are arrays of the Models that returned said indexes
     * @param Array $Models
     * @param String $method
     * @param Array $arguments
     * @param Boolean $useModelIds Use Models' ids as their local index?
     * @return Array
     */
    public static function groupModelsByMethodReturn(
        array $Models,
        $method,
        array $arguments = array(),
        $useModelIds = true
    ) {
        if (empty($method)) throw new ExceptionBase('Invalid method provided');
        $return = array();
        foreach ($Models as $Model) {
            if (!is_a($Model, 'Model')) throw new ExceptionBase('Non-Model inside ' . __FUNCTION__);
            $key = call_user_func_array(array($Model, $method), $arguments);
            if (!array_key_exists($key, $return)) $return[$key] = array();
            if ($useModelIds) {
                $return[$key][$Model->id] = $Model;
            } else {
                $return[$key][] = $Model;
            }
        }
        return $return;
    }

    /**
     * With all of the given models, compose an array whose indexes are
     * the distinct returned values from the given method with the given arguments,
     * and whose values are the returned values for the given method,
     * with the given arguments on the given models
     * @param Array $Models
     * @param String $groupingMethod
     * @param Array $groupingArguments
     * @param String $groupingMethod
     * @param Array $groupingArguments
     * @param Boolean $useModelIds Use Models' ids as their local index?
     * @return Array
     */
    public static function groupReturnedValueByMethodReturn(
        array $Models,
        $groupingMethod,
        array $groupingArguments = array(),
        $valueMethod,
        array $valueArguments = array(),
        $useModelIds = true
    ) {
        if (empty($groupingMethod) || empty($valueMethod)) throw new ExceptionBase('Invalid method provided');
        $return = array();
        foreach ($Models as $Model) {
            if (!is_a($Model, 'Model')) throw new ExceptionBase('Non-Model inside ' . __FUNCTION__);
            $key = call_user_func_array(array($Model, $groupingMethod), $groupingArguments);
            if (!array_key_exists($key, $return)) $return[$key] = array();
            $v = call_user_func_array(array($Model, $valueMethod), $valueArguments);
            if ($useModelIds) {
                $return[$key][$Model->id] = $v;
            } else {
                $return[$key][] = $v;
            }
        }
        return $return;
    }

    /**
     * With all of the given Objects, compose an array whose indexes are
     * the distinct returned values from the given method with the given arguments,
     * and whose values are arrays of the Objects that returned said indexes
     * @param Array $Os
     * @param String $Method
     * @param Array $Arguments
     * @return Array
     */
    public static function groupObjectsByMethodReturn(
        array $Os,
        $Method,
        array $Arguments = array()
    ) {
        if (empty($Method)) throw new ExceptionBase('Invalid method provided');
        $return = array();
        foreach ($Os as $O) {
            $key = call_user_func_array(array($O, $Method), $Arguments);
            if (!array_key_exists($key, $return)) $return[$key] = array();
            $return[$key][] = $O;
        }
        return $return;
    }

    /**
     * Given an array of indeterminate depth, return a single
     * dimensional array of all the values inside
     * @param Array $NestedArrays
     * @return Array
     */
    public static function unNest(array $NestedArrays)
    {
        $final = array();
        foreach ($NestedArrays as $Element) {
            self::unNestTo($Element, $final);
        }
        return $final;
    }

    protected static function unNestTo($Element, array &$final)
    {
        if (is_array($Element)) {
            foreach ($Element as $element) {
                self::unNestTo($element, $final);
            }
        } else {
            $final[] = $Element;
        }
    }

}

