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
	$watt_constant = 12;
	
	include ('../config.php');
	
	function error_function ($error_level,$error_message, $error_file,$error_line,$error_context) {
		global $FROM_EMAIL, $ALERT_EMAIL, $SEND_ALERT_EMAILS;
		
		if ($error_level == 2048 || (strpos ($error_message, 'Undefined') >= 0 && strpos ($error_message, 'chartType') >= 0)) {
			return;
		}
		
		# Send the error by email
	    $to = $ALERT_EMAIL;
	    $subject = "Aggregate to 5 Minutes ERROR";
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
	$nowStr = date ("Y-m-d H:i:s", mktime ($now['hours'], $now['minutes'], $now['seconds'], $now['mon'], $now['mday'], $now['year']));
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
		$itCount = 0;
		$ot = 0;
		$otCount = 0;
		$gt = 0;
		$gtCount = 0;
		$storedOutflow = 0;
		$query = "SELECT
					  dailyInflow,
					  dailyOutflow,
					  dailyGeneration,
					  outflow
				  FROM
					  total
				  WHERE
					  siteID = '$siteIdName'";
		/*$result = mysql_query ($query, $dbLink);
		
		while ($row = mysql_fetch_array ($result)) {
			$itCount = ((int) $row['dailyInflow']);
			$it = ($itCount * $SITES_LINES_FACTORS[$siteIdName]['Inflow'] * $watt_constant);
			$otCount = ((int) $row['dailyOutflow']);
			$ot = ($otCount * $SITES_LINES_FACTORS[$siteIdName]['Outflow'] * $watt_constant);
			$gtCount = ((int) $row['dailyGeneration']);
			$gt = ($gtCount * $SITES_LINES_FACTORS[$siteIdName]['Generation'] * $watt_constant);
			$storedOutflow = ((int) $row['outflow']);
			if ($INVERSE_OUTFLOW == 1 && $ot != 0) { $ot *= -1; }
		}
		
		mysql_free_result ($result);*/
		
		# Get the cached times
		$lastCached = '';
		$lastPosted = '';
		$last2Line = '';
		$query = "SELECT
				      daily,
					  lastContact,
					  last2Line
				  FROM
				      cachetime INNER JOIN site ON cachetime.siteID = site.ID
				  WHERE
				      site.ID = '$siteIdName'";
		/*$result = mysql_query ($query, $dbLink);
		
		while ($row = mysql_fetch_array ($result)) {
			$lastCached = strtotime ($row['daily']);
			$lastPosted = strtotime ($row['lastContact']);
			$last2Line = $row['last2Line'];
		}
		
		mysql_free_result ($result);*/
		
		# Get the next point time
		$nextPointDate = '';
		$query = "SELECT
				      DATE_ADD(MAX(pointDate), INTERVAL 5 MINUTE) AS pd
				  FROM 
				      dailydata 
				  WHERE
				      siteID = '$siteIdName'";
		/*$result = mysql_query ($query, $dbLink);
		
		while ($row = mysql_fetch_array ($result)) {
			$nextPointDate = $row['pd'];
		}*/
		
		# Pick the branch we are going to go down
		if ($lastPosted < $lastCached) {
			# We have not gotten any data since the last cache run
			$query = "INSERT INTO
					      dailydata
					  (
					      siteID,
						  pointDate,
						  inflow,
						  outflow,
						  generation
					  )
					  VALUES (
					      '$siteIdName',
						  '$nextPointDate',
						  NULL,
						  NULL,
						  NULL
					  )";
			#mysql_query ($query, $dbLink);
			
			$query = "INSERT INTO
					      daily2linedata
					  (
					      siteID,
						  pointDate,
						  inflowPurchased,
						  inflowMixed,
						  inflowFree,
						  generationStored
					  )
					  VALUES (
					      '$siteIdName',
						  '$nextPointDate',
						  NULL,
						  NULL,
						  NULL,
						  NULL
					  )";
			#mysql_query ($query, $dbLink);
		}
		else {
			# Update the daily table
			$query = "INSERT INTO
					      dailydata
					  (
					      siteID,
						  pointDate,
						  inflow,
						  outflow,
						  generation
					  )
					  VALUES (
					      '$siteIdName',
						  '$nextPointDate',
						  $it,
						  $ot,
						  $gt
					  )";
			#mysql_query ($query, $dbLink);
			
			# Update the daily 2 line table
			$inflowPurchased = 'NULL';
			$inflowMixed = 'NULL';
			$inflowFree = 'NULL';
			
			if ($ot == 0 && $gt == 0) {
				if ($storedOutflow >= $itCount) {
					$storedOutflow -= $itCount;
					$inflowFree = $it;
				}
				else if ($storedOutflow > 0) {
					$storedOutflow = 0;
					$inflowMixed = $it;
				}
				else {
					$inflowPurchased = $it;
				}
			}
			else if ($ot == 0 && $gt > 0) {
				if ($it > 0) {
					if ($storedOutflow >= $itCount) {
						$storedOutflow -= $itCount;
						$inflowFree = $it + $gt;
					}
					else {
						$storedOutflow = 0;
						$inflowMixed = $it + $gt;
					}
				}
				else {
					$inflowFree = $gt;
				}
			}
			else {
				# We have generation and outflow
				$storedOutflow += $otCount;
				
				if ($storedOutflow >= $itCount) {
					$storedOutflow -= $itCount;
					$inflowFree = $it + $gt - $ot;
				}
				else {
					$storedOutflow = 0;
					$inflowMixed = $it + $gt - $ot;
				}
			}
			
			# Check for a change of line type
			if ($inflowPurchased != 'NULL' && $last2Line != 'InflowPurchased') {
				if ($last2Line == 'InflowMixed') {
					$inflowMixed = $inflowPurchased;
				}
				elseif ($last2Line == 'InflowFree') {
					$inflowFree = $inflowPurchased;
				}
				
				$last2Line = 'InflowPurchased';
			}
			elseif ($inflowMixed != 'NULL' && $last2Line != 'InflowMixed') {
				if ($last2Line == 'InflowPurchased') {
					$inflowPurchased = $inflowMixed;
				}
				elseif ($last2Line == 'InflowFree') {
					$inflowFree = $inflowMixed;
				}
				
				$last2Line = 'InflowMixed';
			}
			elseif ($inflowFree != 'NULL' && $last2Line != 'InflowFree') {
				if ($last2Line == 'InflowPurchased') {
					$inflowPurchased = $inflowFree;
				}
				elseif ($last2Line == 'InflowMixed') {
					$inflowMixed = $inflowFree;
				}
				
				$last2Line = 'InflowFree';
			}
			
			$query = "INSERT INTO
					      daily2linedata
					  (
					      siteID,
						  pointDate,
						  inflowPurchased,
						  inflowMixed,
						  inflowFree,
						  generationStored
					  )
					  VALUES (
					      '$siteIdName',
						  '$nextPointDate',
						  $inflowPurchased,
						  $inflowMixed,
						  $inflowFree,
						  $ot
					  )";
			#mysql_query ($query, $dbLink);
		}
		
		# Clear the daily total values
		$query = "UPDATE
					  total
				  SET
					  dailyInflow = 0,
					  dailyOutflow = 0,
					  dailyGeneration = 0,
					  outflow = $storedOutflow
				  WHERE
					  siteID = '$siteIdName'";
		#mysql_query ($query, $dbLink);
		
		# Update the cache times
		$query = "UPDATE
				      cachetime
				  SET
				      daily = '$newCacheTime',
					  last2Line = '$last2Line'
				  WHERE
					  siteID = '$siteIdName'";
		#mysql_query ($query, $dbLink);
	}
	
	# Check the database for an unreported absent site
	$query = "SELECT
			      ID
			  FROM
			      site
			  WHERE
			      DATE_ADD(lastContact, INTERVAL 10 MINUTE) < '$nowStr'
			    AND
			      absenceReported = 0";
	/*$result = mysql_query ($query, $dbLink);
	
	while ($row = mysql_fetch_array ($result)) {
		# Send the absence by email
	    $to = $ABSENT_EMAIL;
	    $subject = "SolarYpsi Absent Reporter";
	    $message = "Missing collector: " . $row['ID'];
	    $headers = "From: $FROM_EMAIL\r\nReply-To: $FROM_EMAIL";
	    ($SEND_ABSENT_EMAILS ? $mail_sent = @mail ($to, $subject, $message, $headers) : '');
	    
	    # Update the database
	    $query = "UPDATE
	    	          site
	    	      SET
	    	          absenceReported = 1
	    	      WHERE
	    	          ID = '" . $row['ID'] . "'";
	    mysql_query ($query, $dbLink);
	}
	
	mysql_free_result ($result);*/
	
	# Close the database link
    mysql_close ($dbLink);
?>