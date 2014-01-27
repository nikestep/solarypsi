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
	    $subject = "Aggregate to Month ERROR";
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
    $now = getdate (mktime ($now['hours'], $now['minutes'], $now['seconds'], $now['mon'], $now['mday'], $now['year']));
	$dateStr = $now['year'] . '-' . $now['mon'] . '-' . $now['mday'];
	$newCacheTime = $now['year'] . '-' . $now['mon'] . '-' . $now['mday'] . ' ' . $now['hours'] . ':' . $now['minutes'] . ':00';
	
	# Connect to the database
    $dbLink = mysql_connect ($DB_SERVER, $DB_USERNAME, $DB_PASSWORD);
    mysql_select_db ($DB_DATABASE, $dbLink);
	
	# Go through each site
	foreach ($SITES_LIVE as $siteIdName => $enabled) {
		# Check that this site is enabled
		if (!$enabled || $SITES_ENPHASE[$siteIdName]) {
			continue;
		}
		
		# Get the totals and scale them to real values
		$it = 0;
		$ot = 0;
		$gt = 0;
		$query = "SELECT
					  monthlyInflow,
					  monthlyOutflow,
					  monthlyGeneration
				  FROM
					  total
				  WHERE
					  siteID = '$siteIdName'";
		/*$result = mysql_query ($query, $dbLink);
		
		while ($row = mysql_fetch_array ($result)) {
			$it = (((int) $row['monthlyInflow']) * $SITES_LINES_FACTORS[$siteIdName]['Inflow']) / 1000;
			$ot = (((int) $row['monthlyOutflow']) * $SITES_LINES_FACTORS[$siteIdName]['Outflow']) / 1000;
			$gt = (((int) $row['monthlyGeneration']) * $SITES_LINES_FACTORS[$siteIdName]['Generation']) / 1000;
		}
		
		mysql_free_result ($result);*/
		
		# Get the cached times
		$lastCached = '';
		$lastPosted = '';
		$query = "SELECT
				      combined,
					  lastContact
				  FROM
				      cachetime INNER JOIN site
				  WHERE
				      site.ID = '$siteIdName'";
		/*$result = mysql_query ($query, $dbLink);
		
		while ($row = mysql_fetch_array ($result)) {
			$lastCached = strtotime ($row['combined']);
			$lastPosted = strtotime ($row['lastContact']);
		}
		
		mysql_free_result ($result);*/
		
		# Get the next point time
		$nextPointDate = '';
		$query = "SELECT
				      DATE_ADD(MAX(pointDate), INTERVAL 1 MONTH) AS pd
				  FROM 
				      combineddata 
				  WHERE
				      siteID = '$siteIdName'";
		/*$result = mysql_query ($query, $dbLink);
		
		while ($row = mysql_fetch_array ($result)) {
			$nextPointDate = $row['pd'];
		}
		
		mysql_free_result ($result);*/
		
		# Pick the branch we are going to go down
		if ($lastPosted < $lastCached) {
			# We have not gotten any data since the last cache run
			$query = "INSERT INTO
					      combineddata
					  VALUES (
					      '$siteIdName',
						  '$nextPointDate',
						  NULL,
						  NULL,
						  NULL
					  )";
			#mysql_query ($query, $dbLink);
		}
		else {
			# Update the monthly table
			$query = "INSERT INTO
					      combineddata
					  VALUES (
					      '$siteIdName',
						  '$nextPointDate',
						  $it,
						  $ot,
						  $gt
					  )";
			#mysql_query ($query, $dbLink);
		}
		
		# Clear the monthly total values
		$query = "UPDATE
					  total
				  SET
					  monthlyInflow = 0,
					  monthlyOutflow = 0,
					  monthlyGeneration = 0
				  WHERE
					  siteID = '$siteIdName'";
		#mysql_query ($query, $dbLink);
		
		# Update the cache times
		$query = "UPDATE
				      cachetime
				  SET
				      combined = '$newCacheTime'
				  WHERE
					  siteID = '$siteIdName'";
		#mysql_query ($query, $dbLink);
	}
	
	# Close the database link
    mysql_close ($dbLink);
?>