<?php
/**
 * This file contains PHP methods that are used by various other PHP scripts.
 *
 * @author Nik Estep
 * @date March 17, 2013
 */


function isYesterday ($date, $idx = 0) {
	$yest = strtotime ('now');
	if ($idx !== 0) {
		for ($i = 0; $i < $idx; $i++) {
			$yest = strtotime ('-365 days', $yest);
		}
	}
	else {
		$yest = strtotime ('-1 days', $yest);
	}
	return $date === date ('Y-m-d', $yest);
}


function datesEqual ($date1, $date2) {
	return strtotime ($date1) === strtotime ($date2);
}


function getMonthName ($month_num, $abbr = FALSE) {
	if ($abbr) {
		switch ($month_num) {
			case 1:
				return "Jan";
			case 2:
				return "Feb";
			case 3:
				return "Mar";
			case 4:
				return "Apr";
			case 5:
				return "May";
			case 6:
				return "Jun";
			case 7:
				return "Jul";
			case 8:
				return "Aug";
			case 9:
				return "Sep";
			case 10:
				return "Oct";
			case 11:
				return "Nov";
			case 12:
				return "Dec";
			default:
				return $month_num;
		}
	}
	else {
		switch ($month_num) {
			case 1:
				return "January";
			case 2:
				return "February";
			case 3:
				return "March";
			case 4:
				return "April";
			case 5:
				return "May";
			case 6:
				return "June";
			case 7:
				return "July";
			case 8:
				return "August";
			case 9:
				return "September";
			case 10:
				return "October";
			case 11:
				return "November";
			case 12:
				return "December";
			default:
				return $month_num;
		}
	}
}
?>