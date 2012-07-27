<?php
defined('PaZsCA8p') or exit;

abstract class UtilsNumber {
	
	/**
	 * Convert the given float into a mixed number
	 * @param Float $float
	 * @param Int $denominator
	 * @param Int $significantDigits
	 * @param Boolean $forceEndFraction (0/n);
	 * @param Boolean $forceWholeNumber (0)
	 * @param Boolean $ordinalSuffice
	 * @return String
	 */
	public static function floatToMixedNumber($float,
											$denominator,
											$significantDigits = 4,
											$forceEndFraction = false,
											$forceWholeNumber = false,
											$ordinalSuffix = true,
											Html $wrapFraction = NULL) {
		$denominator = (int)$denominator;
		$str = $wholeNumber = '';
		$fn = floor($float);
		if ($fn === 0) {
			if ($forceWholeNumber) {
				$wholeNumber = '0';
			}
		} else {
			$wholeNumber = $fn;
		}
		$numerator = '0';
		$curFloat = $float - $fn;
		while (round($numerator / $denominator, $significantDigits) < round($curFloat, $significantDigits)) {
			$numerator++;
		}
		if ($numerator === $denominator) {
			$numerator = '0';
			$wholeNumber++;
		}
		$str = '';
		if ($wholeNumber !== '') {
			$str = $wholeNumber . ' ';
		}
		if ($numerator !== '0' || $forceEndFraction) {
			$fraction = $numerator . '/';
			if ($ordinalSuffix) {
				$fraction .= self::addOrdinalSuffix($denominator);
				if ($numerator !== 1) $fraction .= 's';
			} else {
				$fraction .= $denominator;
			}
			if (!is_null($wrapFraction)) {
				$str .= $wrapFraction->append($fraction);
			} else {
				$str .= $fraction;
			}
		}
		return $str;
	}
	
	/**
	 * Are the given floating point numbers equal?
	 * 
	 * @param float $float1
	 * @param float $float2
	 * @param float $epsilon Tolerance
	 * @return bool
	 */
	public static function floatsEqual($float1, $float2, $epsilon = 0.01) {
		return abs((float)$float1 - (float)$float2) < $epsilon;
	}
	
	public static function addOrdinalSuffix ($num) {
		if (!in_array(($num % 100), array(11,12,13))){
		  switch ($num % 10) {
		    case 1:  return $num . 'st';
		    case 2:  return $num . 'nd';
		    case 3:  return $num . 'rd';
		  }
		}
		return $num . 'th';
	}
	
	/**
	 * Adds leading zeroes to the given number to bring it up to the specified lenght
	 * @param Int $number
	 * @param Int $length
	 * @return String
	 */
	public static function toLength($number, $length) {
		while(strlen($number) < $length) {
			$number = '0' . $number;
		}
		return $number;
	}
	
	public static function perf($n,$p=2,$d=0){return self::percentageFormat($n,$p,$d);}
	public static function percentageFormat($number,$precision=2,$decimalsOutput=0){
		$N = number_format(round((float)$number,$precision)*100,$decimalsOutput,'.','').'%';
		return $N;
	}
	
	public static function ptn($str,$fp=false){return self::percentageToNum($str,$fp);}
	public static function percentageToNum($str,$forcePositive=false){
		return round((float)preg_replace('/[^\d\.'.($forcePositive?'':'-').']+/','',$str) / 100,2);
	}
	
	public static function cf($n){return self::cashFormat($n);}
	public static function cashFormat($num){
		$neg = self::pf($num) < 0 ? '-' : '';
		return $neg.'$'.number_format(self::pf($num,false),2);
	}
	
	public static function ctn($str,$fp=false){return self::cashToNum($str,$fp);}
	public static function cashToNum($str,$forcePositive=false){
		return round((float)preg_replace('/[^\d\.'.($forcePositive?'':'-').']+/','',$str),2);
	}
	
	public static function pf($str,$an=true){return self::parseFloat($str,$an);}
	public static function parseFloat($str,$allowNegative=true){
		return (float)preg_replace('/[^\d\.'.($allowNegative ? '-' : '').']+/','',$str);
	}
	
	public static function pi($str,$an=true){return self::parseInt($str,$an);}
	public static function parseInt($str,$allowNegative=true){
		return (int)preg_replace('/[^\d'.($allowNegative ? '-' : '').']+/','',$str);
	}

	public static function timeFormatSeconds ($secs, $includeSeconds = false)
	{
		$hrs = floor($secs / 60 / 60);
		if ($includeSeconds) {
			$mins = floor(($secs % 3600) / 60);
			$sec = ($secs % 3600) % 60;
			$Ss = $sec == 1 ? '' : 's';
			$sec = ' ' . $sec . ' second' . $Ss;
		} else {
			$mins = round(($secs % 3600) / 60);
			$sec = '';
		}
		$Hs = $hrs == 1 ? '' : 's';
		if ($hrs > 0) {
			 $hrs = $hrs . ' hr' . $Hs . '. ';
		} else {
			$hrs = '';
		}
		$Ms = $mins == 1 ? '' : 's';
		$mins = $mins . ' min' . $Ms . '.';
		return $hrs . $mins . $sec;
	}

}
