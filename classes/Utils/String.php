<?php

abstract class UtilsString
{
	const IMAGE_FILENAME_REGEXP = '/\.(gif|jpg|jpeg|png)$/i';
	const EMAIL_REGEXP = '/[A-Z]+[A-Z\d_\.]*@[^\.]+\.[^\.]+/i';
	const DOMAIN_REGEXP = '/[A-Z\d-]+\.[A-Z]{2,}/i';
	
	/**
	 * Force UTF-8 Encoding of the given string/array-values
	 * @param mixed $input
	 * @return void
	 */
	public static function forceUTF8(&$input)
	{
		if (is_array($input)) {
			foreach ($input as &$val) {
				self::forceUTF8($val);
			}
		} elseif (is_string($input) && mb_detect_encoding($input, 'UTF-8', true) === FALSE) {
			$input = utf8_encode($input);
		}
	}
	
	public static function isHex($str = '', $caseSensitive = false) {
		return preg_match('/[A-F\d]+/' . ($caseSensitive ? '' : 'i'), $str);
	}
	
	public static function isDomain ($str = '') {
		return preg_match(self::DOMAIN_REGEXP, $str);
	}
	
	public static function isEmail ($str = '') {
		return preg_match(self::EMAIL_REGEXP, $str);
	}
	
	public static function urlSafe($str, $allowPeriods = false, $repWith = '-'){
		return preg_replace('/[^A-Z\d' . ($allowPeriods ? '\.' : '') . '_-]+/i', $repWith, $str);
	}

	public static function toPercentage ($str) {
		$chars = str_split($str);
		if (empty($chars)) return 0;
		$n = 0;
		foreach ($chars as $char) {
			$n += ord($char);
		}
		$wholePercentage = (self::whittle($n) - 1) / 8;
		$uniquePercentage = (self::whittle(ord($chars[0])) - 1) / 8;
		$tens = floor($wholePercentage * 9) * 10;
		$ones = floor($uniquePercentage * 10);
		return ($tens + $ones) / 100;
	}
	
	private static function whittle ($num, $allowed = 1) {
        $j = strval($num);
        while (strlen($j) > $allowed) {
			$j = array_sum(str_split($j));
        }
        return $j;
	}
	
	public static function determineEntropy($str) {
		$pots = 0;
		if (preg_match('/[A-Z]/', $str)) {
			$pots += 26;
		}
		if (preg_match('/[a-z]/', $str)) {
			$pots += 26;
		}
		if (preg_match('/\d/', $str)) {
			$pots += 10;
		}
		if (preg_match('/[^\dA-Za-z]/', $str)) {
			$pots += 22;
		}
		return pow($pots, strlen($str));
	}
	
	public static function enoughEntropy($str, $min = NULL) {
		if (is_null($min)) $min = pow(84, 8);
		return self::determineEntropy($str) >= $min;
	}
	
}

