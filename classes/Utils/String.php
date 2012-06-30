<?php
defined('PaZsCA8p') or exit;

abstract class UtilsString {
	const IMAGE_FILENAME_REGEXP = '/\.(gif|jpg|jpeg|png)$/i';
	const EMAIL_REGEXP = '/[A-Z]+[A-Z\d_\.]*@[^\.]+\.[^\.]+/i';
	const DOMAIN_REGEXP = '/[A-Z\d-]+\.[A-Z]{2,}/i';
	
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
	
}

?>