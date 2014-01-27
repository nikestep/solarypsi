<?php
/*
	Copyright (c) 2012 SolarYpsi.org
	
	Permission is hereby granted, free of charge, to any person obtaining a copy of this 
	software and associated documentation files (the "Software"), to deal in the Software 
	without restriction, including without limitation the rights to use, copy, modify, 
	merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit 
	persons to whom the Software is furnished to do so, subject to the following conditions:
	
	The above copyright notice and this permission notice shall be included in all copies or 
	substantial portions of the Software.
	
	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, 
	INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR 
	PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE 
	FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
	OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER 
	DEALINGS IN THE SOFTWARE.
*/
	
	include ('../config.php');
	
	function error_function ($error_level,$error_message, $error_file,$error_line,$error_context) {
		global $FROM_EMAIL, $ALERT_EMAIL, $SEND_ALERT_EMAILS;
		
		if ($error_level == 2048 || (strpos ($error_message, 'Undefined') >= 0 && strpos ($error_message, 'chartType') >= 0)) {
			return;
		}
		
		# Send the error by email
	    $to = $ALERT_EMAIL;
	    $subject = "Retrieve Enphase Data ERROR";
	    $message = "Error Level: $error_level\n" .
	    		   "Error Message: $error_message\n" .
	    		   "Error File: $error_file\n" .
	    		   "Error Line: $error_line\n";
	    $headers = "From: $FROM_EMAIL\r\nReply-To: $FROM_EMAIL";
	    ($SEND_ALERT_EMAILS ? $mail_sent = @mail ($to, $subject, $message, $headers) : '');
	}
	set_error_handler ("error_function");
	
	# Create time of now
    $now = getdate ();
    $now['hours'] += $TIMEOFFSET;
    $now = getdate (mktime ($now['hours'], $now['minutes'], 0, $now['mon'], $now['mday'], $now['year']));
	
	# Connect to the database
    $dbLink = mysql_connect ($DB_SERVER, $DB_USERNAME, $DB_PASSWORD);
    mysql_select_db ($DB_DATABASE, $dbLink);
	
	# Go through each site
	foreach ($SITES_ENPHASE as $siteIdName => $enabled) {
		# Check that this site is enabled
		if (!$enabled) {
			continue;
		}
		
		# Get the Enphase API information
		$api = $ENPHASE_API[$SITES_TO_ENPHASE[$siteIdName]['code']];
		
		# Get the cache times
		$start = '';
		$end = '';
		$lastHour = 0;
		$lastDay = 0;
		$lastMonth = 0;
		$query = "SELECT
				      daily,
				      weekly,
				      yearly,
				      combined
				  FROM
				      cachetime
				  WHERE
				      siteID = '$siteIdName'";
		/*$result = mysql_query ($query, $dbLink);
		
		while ($row = mysql_fetch_array ($result)) {
			$start = getdate (strtotime ($row['daily']));
			$lastHour = getdate (strtotime ($row['weekly']));
			$lastDay = getdate (strtotime ($row['yearly']));
			$lastMonth = getdate (strtotime ($row['combined']));
		}
		
		mysql_free_result ($result);*/
		
		# Get the running totals
		$hourTotal = 0;
		$dayTotal = 0;
		$monthTotal = 0;
		$query = "SELECT
				      weeklyGeneration,
				      yearlyGeneration,
				      monthlyGeneration
				  FROM
				  	  total
				  WHERE
				      siteID = '$siteIdName'";
		/*$result = mysql_query ($query, $dbLink);
		
		while ($row = mysql_fetch_array ($result)) {
			$hourTotal = intval ($row['weeklyGeneration']);
			$dayTotal = intval ($row['yearlyGeneration']);
			$monthTotal = intval ($row['monthlyGeneration']);
		}
		
		mysql_free_result ($result);*/
		
		# Go ahead and suck in the JSON data from Enphase
		$url = 'https://api.enphaseenergy.com/api/systems/' .
			   $api['systemID'] .
			   '/stats?key=' .
			   $api['apiKey'] .
			   '&start=' .
			   make_enphase_time ($start, $api['tzOffset']) .
			   '&end=' .
			   make_enphase_time ($now, $api['tzOffset']);
		echo "$url<br />\n";
		$data = json_decode (file_get_contents ($url));
		
		# If we got a reason, the request was not successful, so we have to skip this site
		if ($data->reason != "") { 
			echo "$siteIdName has reason: " . $data->reason . "<br />";
			echo "    " . make_mysql_time ($start) . ", " . make_mysql_time ($end) . "<br />";
			echo "    $url<br />";
			continue;
		}
		
		# Pull out all of the data from the request
		$index = 0;
		while ($index < sizeof ($data->intervals)) {
			echo $data->intervals[$index]->end_date . "<br />\n";
			$end = getdate (strtotime (str_replace ($api['tzOffset'], '', $data->intervals[$index]->end_date)));
			$start = getdate (mktime ($end['hours'], $end['minutes'] - 5, $end['seconds'], 
									  $end['mon'], $end['mday'], $end['year']));
			# Check for any aggregation that needs to be done
			if ($end['hours'] != $lastHour['hours']) {
				$query = "INSERT INTO
						      weeklydata
						  VALUES
						  (
						      '$siteIdName',
						      '" . make_mysql_time ($lastHour) . "',
						      NULL,
						      NULL,
						      " . ($hourTotal * $SITES_TO_ENPHASE[$siteIdName]['portion']) . "
					      )
					      ON DUPLICATE KEY UPDATE
					          generation = " . ($hourTotal * $SITES_TO_ENPHASE[$siteIdName]['portion']);
				#mysql_query ($query, $dbLink);
				$hourTotal = 0;
				$lastHour = getdate (mktime ($lastHour['hours'] + 1, 0, 0, $lastHour['mon'], $lastHour['mday'], $lastHour['year']));
			}
			if ($end['mday'] != $lastDay['mday']) {
				$query = "INSERT INTO
						      yearlydata
						  VALUES
						  (
						      '$siteIdName',
						      '" . make_mysql_time ($lastDay) . "',
						      NULL,
						      NULL,
						      " . ($dayTotal * $SITES_TO_ENPHASE[$siteIdName]['portion']) . "
					      )
					      ON DUPLICATE KEY UPDATE
					          generation = " . ($dayTotal * $SITES_TO_ENPHASE[$siteIdName]['portion']);
				#mysql_query ($query, $dbLink);
				$dayTotal = 0;
				$lastDay = getdate (mktime (0, 0, 0, $lastDay['mon'], $lastDay['mday'] + 1, $lastDay['year']));
			}
			if ($end['mon'] != $lastMonth['mon']) {
				$query = "INSERT INTO
						      combineddata
						  VALUES
						  (
						      '$siteIdName',
						      '" . make_mysql_time ($lastMonth) . "',
						      NULL,
						      NULL,
						      " . ($monthTotal * $SITES_TO_ENPHASE[$siteIdName]['portion']) . "
					      )
					      ON DUPLICATE KEY UPDATE
					          generation = " . ($monthTotal * $SITES_TO_ENPHASE[$siteIdName]['portion']);
				#mysql_query ($query, $dbLink);
				
				# Update the money total
				$gtMoney = ($monthTotal * $SITES_TO_ENPHASE[$siteIdName]['portion']) * $SITES_COSTS[$siteIdName]['Credit'];
				$query = "UPDATE
							  site
						  SET
							  moneySaved = moneySaved + $gtMoney
						  WHERE
							  ID = '$siteIdName'";
				#mysql_query ($query, $dbLink);
				
				$monthTotal = 0;
				$lastMonth = getdate (mktime (0, 0, 0, $lastMonth['mon'] + 1, 1, $lastMonth['year']));
			}
			
			# Insert the new daily chart point
			$query = "INSERT INTO
					      dailydata
					  VALUES
					  (
					      '$siteIdName',
					      '" . make_mysql_time ($start) ."',
					      NULL,
					      NULL,
					      " . ($data->intervals[$index]->powr * $SITES_TO_ENPHASE[$siteIdName]['portion']) . "
					  )
					  ON DUPLICATE KEY UPDATE
						  generation = " . ($data->intervals[$index]->powr * $SITES_TO_ENPHASE[$siteIdName]['portion']);
			#mysql_query ($query, $dbLink);
			
			# Update the totals
			$hourTotal += $data->intervals[$index]->enwh;
			$dayTotal += $data->intervals[$index]->enwh;
			$monthTotal += $data->intervals[$index]->enwh;
			
			# Go to next index
			$index += 1;
		}
		
		# Store cache times and totals if we have an end time or see if we need to pad in NULLs
		# Having an end time means we retrieved at least one data point
		if ($end != '') {
			$query = "UPDATE
						  cachetime
					  SET
						  daily = '" . make_mysql_time ($end) . "',
						  weekly = '" . make_mysql_time ($lastHour) . "',
						  yearly = '" . make_mysql_time ($lastDay) . "',
						  combined = '" . make_mysql_time ($lastMonth) . "'
					  WHERE
						  siteID = '$siteIdName'";
			#mysql_query ($query, $dbLink);
			
			# Store our totals
			$query = "UPDATE
						  total
					  SET
						  weeklyGeneration = $hourTotal,
						  yearlyGeneration = $dayTotal,
						  monthlyGeneration = $monthTotal
					  WHERE
						  siteID = '$siteIdName'";
			#mysql_query ($query, $dbLink);
		}
		else {
			# Pad in days
			while (!dates_equal ($start, $now)) {
				$query = "INSERT INTO
							  dailydata
						  VALUES
						  (
							  '$siteIdName',
							  '" . make_mysql_time ($start) ."',
							  NULL,
							  NULL,
							  NULL
						  )";
				#mysql_query ($query, $dbLink);
				$start = getdate (mktime ($start['hours'], $start['minutes'] + 5, 0, $start['mon'], $start['mday'], $start['year']));
			}
			# Pad in hours
			while ($lastHour['hours'] != $now['hours']) {
				$query = "INSERT INTO
						      weeklydata
						  VALUES
						  (
						      '$siteIdName',
						      '" . make_mysql_time ($lastHour) . "',
						      NULL,
						      NULL,
						      NULL
					      )";
				#mysql_query ($query, $dbLink);
				$lastHour = getdate (mktime ($lastHour['hours'] + 1, 0, 0, $lastHour['mon'], $lastHour['mday'], $lastHour['year']));
			}
			
			# Pad in days
			while ($lastDay['mday'] != $now['mday']) {
				$query = "INSERT INTO
						      yearlydata
						  VALUES
						  (
						      '$siteIdName',
						      '" . make_mysql_time ($lastDay) . "',
						      NULL,
						      NULL,
						      NULL
					      )";
				#mysql_query ($query, $dbLink);
				$lastDay = getdate (mktime (0, 0, 0, $lastDay['mon'], $lastDay['mday'] + 1, $lastDay['year']));
			}
			
			# Pad in monhts
			while ($lastMonth['mon'] != $now['mon']) {
				$query = "INSERT INTO
						      combineddata
						  VALUES
						  (
						      '$siteIdName',
						      '" . make_mysql_time ($lastMonth) . "',
						      NULL,
						      NULL,
						      NULL
					      )";
				#mysql_query ($query, $dbLink);
				$lastMonth = getdate (mktime (0, 0, 0, $lastMonth['mon'] + 1, 1, $lastMonth['year']));
			}
		}
	}
	
	# Close the database link
    mysql_close ($dbLink);
?>

<?php
	# Are two dates equal down to the minute
	function dates_equal ($date1, $date2) {
		return $date1['year'] == $date2['year'] &&
			   $date1['mon'] == $date2['mon'] &&
			   $date1['mday'] == $date2['mday'] &&
			   $date1['hours'] == $date2['hours'] &&
			   $date1['minutes'] == $date2['minutes'];
	}
	
	# Create a string date/time specifically for the Enphase API call
	function make_enphase_time ($date, $tz) {
		$str = strval ($date['year']) . '-';
		if ($date['mon'] < 10) {
			$str .= '0';
		}
		$str .= strval ($date['mon']) . '-';
		if ($date['mday'] < 10) {
			$str .= '0';
		}
		$str .= strval ($date['mday']) . 'T';
		if ($date['hours'] < 10) {
			$str .= '0';
		}
		$str .= strval ($date['hours']) . ':';
		if ($date['minutes'] < 10) {
			$str .= '0';
		}
		$str .= strval ($date['minutes']) . $tz;
		return $str;
	}
	
	# Create a string date/time specifically for MySQL
	function make_mysql_time ($date) {
		$str = strval ($date['year']) . '-';
		if ($date['mon'] < 10) {
			$str .= '0';
		}
		$str .= strval ($date['mon']) . '-';
		if ($date['mday'] < 10) {
			$str .= '0';
		}
		$str .= $date['mday'] . ' ';
		if ($date['hours'] < 10) {
			$str .= '0';
		}
		$str .= $date['hours'] . ':';
		if ($date['minutes'] < 10) {
			$str .= '0';
		}
		$str .= $date['minutes'] . ':00';
		return $str;
	}
?>