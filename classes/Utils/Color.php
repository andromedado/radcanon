<?php

abstract class UtilsColor {
	
	protected static function HSVtoRGB ($h, $s, $v) {
		$color = new stdClass;
		$color->r = 0;
		$color->g = 0;
		$color->b = 0;
		if ( $s == 0 ) {
			$color->r = $color->g = $color->b = $v;
			return;
		}
		$h /= 60;
		$i = floor( $h );
		$f = $h - $i;
		$p = $v * ( 1 - $s );
		$q = $v * ( 1 - $s * $f );
		$t = $v * ( 1 - $s * ( 1 - $f ) );
		switch( $i ) {
			case 0:
				$color->r = $v;
				$color->g = $t;
				$color->b = $p;
				break;
			case 1:
				$color->r = $q;
				$color->g = $v;
				$color->b = $p;
				break;
			case 2:
				$color->r = $p;
				$color->g = $v;
				$color->b = $t;
				break;
			case 3:
				$color->r = $p;
				$color->g = $q;
				$color->b = $v;
				break;
			case 4:
				$color->r = $t;
				$color->g = $p;
				$color->b = $v;
				break;
			default:		// case 5:
				$color->r = $v;
				$color->g = $p;
				$color->b = $q;
				break;
		}
		return $color;
	}
	
	/**
	 * Convert the give Hue, Saturation and Value to an RGB String
	 * @param Float $h Hue 0-359
	 * @param Float $s Saturation 0-1
	 * @param Float $v Value 0-1
	 * @return String
	 */
	public static function hsvToHex ($h, $s, $v) {
		$color = self::HSVtoRGB($h, $s, $v);
		$color->r = base_convert(round($color->r * 255), 10, 16);
		if (strlen($color->r) < 2) $color->r = '0' . $color->r;
		$color->g = base_convert(round($color->g * 255), 10, 16);
		if (strlen($color->g) < 2) $color->g = '0' . $color->g;
		$color->b = base_convert(round($color->b * 255), 10, 16);
		if (strlen($color->b) < 2) $color->b = '0' . $color->b;
		return '#' . $color->r . $color->g . $color->b;
	}
	
	/**
	 * Convert the given string into a Hue Value
	 * Optionally within bounds
	 * @param String $str
	 * @param Integer $lowerBound
	 * @param Integer $upperBound
	 * @return Integer
	 */
	public static function stringToHue ($str, $lowerBound = 0, $uppderBound = 359) {
		return $lowerBound + (UtilsString::toPercentage($str) * ($uppderBound - $lowerBound));
	}
	
}

?>