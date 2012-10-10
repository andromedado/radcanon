<?php

abstract class UtilsDate {
	
	/**
	 * Given two dates (String or UnixTimestamp) is 
	 * the first greater than the second? [for use in `usort`]
	 * @param mixed $date1
	 * @param mixed $date2
	 * @return Integer
	 */
	public static function monthCmp($date1, $date2)
	{
		if (is_string($date1)) $date1 = (int)strtotime($date1);
		if (is_string($date2)) $date2 = (int)strtotime($date2);
		$m1 = date('n', $date1);
		$m2 = date('n', $date2);
		if ($m1 === $m2) return 0;
		return $m1 < $m2 ? -1 : 1;
	}
	
	/**
	 * Reformat the given date string into the new form
	 * @param String $date
	 * @param String $format
	 * @return String
	 */
	public static function redoDateFormat($date, $format = 'm/d/Y') {
		return date($format, strtotime($date));
	}
	
	/**
	 * Do the given date/time strings occur in the same day?
	 * @param String $date1
	 * @param String $date2
	 * @return Boolean
	 */
	public static function stringsSameDay($date1, $date2) {
		return self::redoDateFormat($date1) === self::redoDateFormat($date2);
	}
	
	/**
	 * How many days were/are there in the month of the given Timestamp?
	 * @param Int $timestamp
	 * @param Const $calendar
	 * @return Int
	 */
	public static function daysInMonth($timestamp, $calendar = CAL_GREGORIAN) {
		return cal_days_in_month($calendar, date('m', $timestamp), date('Y', $timestamp));
	}
	
	/**
	 * Get the bounds of the given year
	 * The currently set TimeZone is Used
	 * 
	 * @param int $year
	 * @param bool $inclusive Inclusive Bounds? (or exclusive)
	 * @return array (Begin,End)
	 */
	public static function getYearBeginEnd($year, $inclusive = true) {
		if ($inclusive) return self::getInclusiveYearBeginEnd($year);
		return self::getExclusiveYearBeginEnd($year);
	}
	
	/**
	 * Get the exclusive bounds of the given year
	 * The currently set TimeZone is Used
	 * 
	 * @param int $year
	 * @return array (Begin,End)
	 */
	public static function getExclusiveYearBeginEnd($year) {
		$begin = strtotime($year . '-01-01 12:00:00AM') - 1;// . date('T')) - 1;
		$end = strtotime($year . '-12-31 12:00:00AM') - 1;// . date('T'));
		return array($begin, $end);
	}
	
	/**
	 * Get the inclusive bounds of the given year
	 * The currently set TimeZone is Used
	 * 
	 * @param int $year
	 * @return array (Begin,End)
	 */
	public static function getInclusiveYearBeginEnd($year) {
		$begin = strtotime($year . '-01-01 12:00:00AM');// . date('T'));
		$end = strtotime($year . '-12-31 12:00:00AM') - 1;// . date('T')) - 1;
		return array($begin, $end);
	}
	
	/**
	 * Get the bounds of the given quarter
	 * The currently set TimeZone is Used
	 * 
	 * @param int $quarter 1-4
	 * @param int $year
	 * @param bool $inclusive Inclusive Bounds? (or exclusive)
	 * @return array (Begin,End)
	 */
	public static function getQuarterBeginEnd($quarter, $year, $inclusive = true) {
		if ($inclusive) return self::getInclusiveQuarterBeginEnd($quarter, $year);
		return self::getExclusiveQuarterBeginEnd($quater, $year);
	}
	
	/**
	 * Get the exclusive bounds of the given quarter
	 * The currently set TimeZone is Used
	 * 
	 * @param int $quarter 1-4
	 * @param int $year
	 * @return array (Begin,End)
	 */
	public static function getExclusiveQuarterBeginEnd($quarter, $year) {
		$quarter = $quarter % 5;
		if ($quarter < 1) $quarter = 1;
		$bTS = strtotime($year . '-' . ((3 * $quarter) - 2) . '-1');
		$eTS = strtotime($year . '-' . (3 * $quarter) . '-1');
		$begin = strtotime(date('Y-m', $bTS) . '-01 12:00:00AM') - 1;// ' . date('T') // Timezone throwing off when going between daylight savings and not
		$end = strtotime(date('Y-m', strtotime('+1 month', $eTS)) . '-01 12:00:00AM');// . date('T'));
		return array($begin, $end);
	}
	
	/**
	 * Get the inclusive bounds of the given quarter
	 * The currently set TimeZone is Used
	 * 
	 * @param int $quarter 1-4
	 * @param int $year
	 * @return array (Begin,End)
	 */
	public static function getInclusiveQuarterBeginEnd($quarter, $year) {
		$quarter = $quarter % 5;
		if ($quarter < 1) $quarter = 1;
		$bTS = strtotime($year . '-' . ((3 * $quarter) - 2) . '-1');
		$eTS = strtotime($year . '-' . (3 * $quarter) . '-1');
		$beginDate = date('Y-m', $bTS) . '-01 12:00:00AM';// . date('T');
		$begin = strtotime($beginDate);
		$endDate = date('Y-m', strtotime('+1 month', $eTS)) . '-01 12:00:00AM';// . date('T');
		$end = strtotime($endDate) - 1;
		return array($begin, $end);
	}
	
	/**
	 * Get an Html <select> with Month's long names
	 * 
	 * @param mixed $info Passed to Html's Constructor
	 * @param int $selected Selected Month
	 * @param bool $useNameForId
	 * @return Html
	 */
	public static function getLongMonthSelect($info, $selected, $useNameForId = false) {
		$ms = array();
		for($i = 1; $i < 13; $i++) {
			$j = $i < 10 ? '0' . $i : $i;
			$ms[$i] = date('F', strtotime('2000-' . $j . '-01'));
		}
		return UtilsHtm::select($ms, $info, $selected, $useNameForId);
	}
	
	/**
	 * Get the bounds of the month within which the timestamp falls
	 * If no timestamp is provided, the current month is used
	 * The currently set TimeZone is Used
	 * 
	 * @param int $timeStamp UnixTimestamp
	 * @param bool $inclusive Inclusive Bounds? (or exclusive)
	 * @return array (Begin,End)
	 */
	public static function getMonthBeginEnd($timeStamp = NULL, $inclusive = true) {
		if ($inclusive) return self::getInclusiveMonthBeginEnd($timeStamp);
		return self::getExclusiveMonthBeginEnd($timeStamp);
	}
	
	/**
	 * Get the exclusive bounds of the month within which the timestamp falls
	 * If no timestamp is provided, the current month is used
	 * The currently set TimeZone is Used
	 * 
	 * @param int $timeStamp UnixTimestamp
	 * @return array (Begin,End)
	 */
	public static function getExclusiveMonthBeginEnd($timeStamp = NULL) {
		if (is_null($timeStamp)) $timeStamp = time();
		$begin = strtotime(date('Y-m', $timeStamp) . '-01 12:00:00AM') - 1;// . date('T')) - 1;
		$end = strtotime(date('Y-m', strtotime('+1 month', $timeStamp)) . '-01 12:00:00AM');// . date('T'));
		return array($begin, $end);
	}
	
	/**
	 * Get the inclusive bounds of the month within which the timestamp falls
	 * If no timestamp is provided, the current month is used
	 * The currently set TimeZone is Used
	 * 
	 * @param int $timeStamp UnixTimestamp
	 * @return array (Begin,End)
	 */
	public static function getInclusiveMonthBeginEnd($timeStamp = NULL) {
		if (is_null($timeStamp)) $timeStamp = time();
		$begin = strtotime(date('Y-m', $timeStamp) . '-01 12:00:00AM');// . date('T'));
		$end = strtotime(date('Y-m', strtotime('+1 month', $timeStamp)) . '-01 12:00:00AM') - 1;// . date('T')) - 1;
		return array($begin, $end);
	}
	
	/**
	 * Get the Occurrence of the given day (timestamp)
	 * 2012-10-09 is the '2'nd occurence of Tuesday for October 2012
	 * @param Integer $timestamp
	 * @return Integer
	 */
	public static function getDayOccurrenceInMonth($timestamp)
	{
		return ceil(date('j', $timestamp) / 7);
	}
	
	/**
	 * Determine the Quarter of the given timestamp
	 * @param Integer $timestamp
	 * @return Integer
	 */
	public static function determineQuarter($timestamp)
	{
		return ceil(date('n', $timestamp) / 3);
	}
	
	/**
	 * Given two dates, is the first one an earlier day than the second?
	 * @param String $this
	 * @param String $that
	 * @return Boolean
	 */
	public static function thisDayIsBeforeThatDay($this, $that)
	{
		$thisTs = (int)strtotime($this);
		$thatTs = (int)strtotime($that);
		return $thisTs < $thatTs && date('Y-m-d', $thisTs) !== date('Y-m-d', $thatTs);
	}
	
}

