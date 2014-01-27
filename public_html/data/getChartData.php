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
	
	# Include other files
    include ('config.php');
    include ('methods.php');
    
    function error_function ($error_level,$error_message, $error_file,$error_line,$error_context) {
        global $FROM_EMAIL, $ALERT_EMAIL, $SEND_ALERT_EMAILS;

        if ($error_level == 2048 || (strpos ($error_message, 'Undefined') >= 0 && strpos ($error_message, 'chartType') >= 0)) {
			return;
		}

        # Send the error by email
        $to = $ALERT_EMAIL;
        $subject = "Get Chart Data ERROR";
        $message = "Error Level: $error_level\n" .
                   "Error Message: $error_message\n" .
                   "Error File: $error_file\n" .
                   "Error Line: $error_line\n" .
                   "Query String: " . $_SERVER['QUERY_STRING'] . "\n" .
                   "Remote IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
        $headers = "From: $FROM_EMAIL\r\nReply-To: $FROM_EMAIL";
        ($SEND_ALERT_EMAILS ? $mail_sent = @mail ($to, $subject, $message, $headers) : '');
    }
    set_error_handler ("error_function");
    
    # Define some mappings
    $chartTypeToTable = array ('DAILY' => 'dailydata',
    						   'DAILY2LINE' => 'daily2linedata',
    						   'WEEKLY' => 'weeklydata',
    						   'YEARLY' => 'yearlydata',
    						   'COMBINED' => 'combineddata');
    
    $chartTypeToLengths = array ('DAILY' => 255,
    							 'DAILY2LINE' => 255,
    							 'WEEKLY' => 168,
    							 'YEARLY' => 365,
    							 'COMBINED' => 12);
    
    # Get URL parameters
    $siteIdName = $_REQUEST['siteIdName'];
    $chartType = $_REQUEST['chartType'];
    $chartDay = '';
    if (isset ($_REQUEST['chartDay'])) {
    	$chartDay = $_REQUEST['chartDay'];
    }
    $chartIndex = 0;
    if (isset ($_REQUEST['chartNumber'])) {
        $chartIndex = $_REQUEST['chartNumber'];
    }
    $bigStyle = 0;
    if (isset ($_REQUEST['bigStyle'])) {
        $bigStyle = 1;
    }
    $xmlOut = 0;
    if (isset ($_REQUEST['xmlOut'])) {
        $xmlOut = 1;
    }

    # Declare the response type
    if ($xmlOut == 1) {
        header ('Content-type: text/xml');
    }
    else {
        header ('Content-type: application/json');
    }
    
    # Connect to the database
    $dbLink = mysql_connect ($DB_SERVER, $DB_USERNAME, $DB_PASSWORD);
    mysql_select_db ($DB_DATABASE, $dbLink);
    
    # Determine the time stuff
    $currTime = getdate ();
    $currTime['hours'] += $TIMEOFFSET;
    $chartYear = '';
    
    if ($chartDay == '') {
        $currTime = getdate (mktime ($currTime['hours'], $currTime['minutes'],
                                     $currTime['seconds'], $currTime['mon'],
                                     $currTime['mday'], $currTime['year']));
        $chartYear = $currTime['year'];
    }
    else {
        # Correct the time so that our date doesn't get changed
        if ($currTime['hours'] == 24) {
            $currTime['hours'] = 0;
        }
        
        # 2009-6-12
        $m = 0;
        $d = 0;
        $y = substr ($chartDay, 0, 4);
        if (strpos (substr($chartDay, 5, 2), "-") > 0) {
            $m = substr ($chartDay, 5, 1);
            $d = substr ($chartDay, 7, 2);
        }
        # 2009-06-12
        else {
            $m = substr ($chartDay, 5, 2);
            $d = substr ($chartDay, 8, 2);
        }
        
        $currTime = getdate (mktime ($currTime['hours'], $currTime['minutes'],
                                     $currTime['seconds'], $m, $d, $y));
        $chartYear = $currTime['year'];
    }
    
    if ($chartType == 'COMBINED') {
        $currTime['mday'] = 1;
        $currTime = getdate (mktime ($currTime['hours'], $currTime['minutes'],
                                     $currTime['seconds'], $currTime['mon'], $currTime['mday'], $chartYear));
    }
    
    # Get the sunrise etc if necessary
    $sunrise = '';
    $localNoon = '';
    $sunset = '';
    if ($chartType == 'DAILY' || $chartType == 'DAILY2LINE') {
    	if ($siteIdName == 'comparisons') {
    		$siteIdName = 'foodcoop';
    	}
    	
    	# Build the query
    	$query = "SELECT
                      sunrise,
                      localNoon,
                      sunset
                  FROM
                      atmospheredata
                  WHERE
                      siteID = '$siteIdName'
                    AND
                      date = '" .
                      $currTime['year'] .
                      "-" .
                      $currTime['mon'] .
                      "-" .
                      $currTime['mday'] .
                      "'";
    	
    	# Execute the query
    	$results = mysql_query ($query, $dbLink);
    	
    	# Pull out results if they are valid
    	if ($results != false && mysql_num_rows ($results) == 1) {
    		$row = mysql_fetch_array ($results);
    		$sunrise = getdate (strtotime ($row['sunrise']));
    		$localNoon = getdate (strtotime ($row['localNoon']));
    		$sunset = getdate (strtotime ($row['sunset']));
    	}
    	
    	# Free the result
    	mysql_free_result ($results);
    }
    
    # Start building the XML
    $xml = "";
    
    # Begin with the category section
    $startDate = '';
    $endDate = '';
    $xml = generate_category_section ($chartType, $currTime, $sunrise, $localNoon, $sunset);
    if ($chartType == 'DAILY' || $chartType == 'DAILY2LINE') {
    	# Prepare some dates
		$now2 = getdate ();
		$now2['hours'] += $TIMEOFFSET;
		$now2 = getdate (mktime ($now2['hours'], $now2['minutes'],
								 $now2['seconds'], $now2['mon'],
								 $now2['mday'], $now2['year']));
		$requestedDate = date ("F j, Y", mktime (0, 0, 0,
												 $currTime['mon'], $currTime['mday'], $currTime['year']));
		$currentDate = date ("F j, Y", mktime (0, 0, 0,
											   $now2['mon'], $now2['mday'], $now2['year']));
		
		if (strcmp ($requestedDate, $currentDate) == 0) {
			if ($chartType == 'DAILY') {
				$xml = str_replace ("caption='REPLACEME'", "caption='Today'", $xml);
			}
			else {
				$xml = str_replace ("caption='REPLACEME'", "caption='Consumption Today'", $xml);
			}
		}
		else {
			if ($chartType == 'DAILY') {
				$xml = str_replace ("caption='REPLACEME'", "caption='$requestedDate'", $xml);
			}
			else {
				$xml = str_replace ("caption='REPLACEME'", "caption='Consumption on $requestedDate'", $xml);
			}
		}
		
		$startDate = $currTime['year'] . '-' . $currTime['mon'] . '-' . $currTime['mday'] . ' 00:00:00';
		$endDate = $currTime['year'] . '-' . $currTime['mon'] . '-' . $currTime['mday'] . ' 23:59:59';
    }
    elseif ($chartType == 'WEEKLY') {
    	$startDay = $currTime;
		$endDay = $currTime;
		$startDay['mday'] -= ($chartIndex * 7);
		$startDay = getdate (mktime (0, 0, 0, $startDay['mon'], $startDay['mday'], $startDay['year']));
		$endDay['mday'] -= (($chartIndex + 1) * 7);
		$endDay = getdate (mktime (0, 0, 0, $endDay['mon'], $endDay['mday'], $endDay['year']));
		$startDate = $endDay['year'] . '-' . $endDay['mon'] . '-' . $endDay['mday'] . ' 00:00:00';
		$endDate = $startDay['year'] . '-' . $startDay['mon'] . '-' . $startDay['mday'] . ' 23:59:59';
		
    	if ($chartIndex == 0) {
			$xml = str_replace ("caption='REPLACEME'", "caption='The Past Week'", $xml);
		}
		else {
			$str = $startDay['month'] . " " . $startDay['mday'] . ", " . $startDay['year'] . " - " .
			   	   $endDay['month'] . " " . $endDay['mday'] . ", " . $endDay['year'];
			$xml = str_replace ("caption='REPLACEME'", "caption='$str'", $xml);
		}
    }
    elseif ($chartType == 'YEARLY') {
    	$startDay = $currTime;
		$endDay = $currTime;
		$startDay['mday'] -= ($chartIndex * 365);
		$startDay = getdate (mktime (0, 0, 0, $startDay['mon'], $startDay['mday'], $startDay['year']));
		$endDay['mday'] -= (($chartIndex + 1) * 365) - 1;
		$endDay = getdate (mktime (0, 0, 0, $endDay['mon'], $endDay['mday'], $endDay['year']));
		$startDate = $endDay['year'] . '-' . $endDay['mon'] . '-' . $endDay['mday'];
		$endDate = $startDay['year'] . '-' . $startDay['mon'] . '-' . $startDay['mday'];
		
    	if ($chartIndex == 0) {
			$xml = str_replace ("caption='REPLACEME'", "caption='The Past Year'", $xml);
		}
		else {
			$str = $startDay['month'] . " " . $startDay['mday'] . ", " . $startDay['year'] . " - " .
				   $endDay['month'] . " " . $endDay['mday'] . ", " . $endDay['year'];
			$xml = str_replace ("caption='REPLACEME'", "caption='$str'", $xml);
		}
    }
    elseif ($chartType == 'COMBINED') {
    	$xml = str_replace ("showNames='0'", "showNames='1'", $xml);
    	
    	$startDay = $currTime;
		$endDay = $currTime;
		$startDay['year'] -= $chartIndex;
		$startDay['mon'] -= 1;
		$startDay = getdate (mktime (0, 0, 0, $startDay['mon'], $startDay['mday'], $startDay['year']));
		$endDay['year'] -= ($chartIndex + 1);
		$endDay = getdate (mktime (0, 0, 0, $endDay['mon'], $endDay['mday'], $endDay['year']));
		$startDate = $endDay['year'] . '-' . $endDay['mon'] . '-' . $endDay['mday'];
		$endDate = $startDay['year'] . '-' . $startDay['mon'] . '-' . $startDay['mday'];
		
		if ($chartIndex == 0) {
			$xml = str_replace ("caption='REPLACEME'", "caption='Monthly Usage'", $xml);
		}
		else {
			$str = $startDay['month'] . " " . $startDay['year'] . " - " .
				   $endDay['month'] . " " . $endDay['year'];
			$xml = str_replace ("caption='REPLACEME'", "caption='Monthly Usage $str'", $xml);
		}
    }
    
	if ($_REQUEST['siteIdName'] != 'comparisons') {
		if ($chartType != 'DAILY2LINE') {
			$inflowXML = '';
			$outflowXML = '';
			$generationXML = '';
			$query = "SELECT
					      inflow,
					      outflow,
					      generation
					  FROM
					  	  " . $chartTypeToTable[$chartType] . "
					  WHERE
					      siteID = '$siteIdName'
					    AND
					      pointDate BETWEEN '$startDate' AND '$endDate'";
			
			$results = mysql_query ($query, $dbLink);
			
			$count = 0;
			$foundData = false;
			while ($row = mysql_fetch_array ($results)) {
				if ($row['inflow'] == '') {
					$inflowXML .= "<set />\n";
				}
				else {
					$foundData = true;
					$inflowXML .= "<set value='" . $row['inflow'] . "' />\n";
				}
				if ($row['outflow'] == '') {
					$outflowXML .= "<set />\n";
				}
				else {
					$foundData = true;
					$outflowXML .= "<set value='" . $row['outflow'] . "' />\n";
				}
				if ($row['generation'] == '') {
					$generationXML .= "<set />\n";
				}
				else {
					$foundData = true;
					$generationXML .= "<set value='" . $row['generation'] . "' />\n";
				}
				
				$count += 1;
			}
			
			mysql_free_result ($results);
			
			# Check for no data
			if (!$foundData) {
				exit;
			}
			
			# Make sure the datasets are full
			if ($count > 0) {
				if ($chartType == 'DAILY') {
					while ($count < $chartTypeToLengths[$chartType]) {
						$inflowXML .= "<set />\n";
						$outflowXML .= "<set />\n";
						$generationXML .= "<set />\n";
						$count += 1;
					}
				}
				else {
					while ($count < $chartTypeToLengths[$chartType]) {
						$inflowXML = "<set />\n" . $inflowXML;
						$outflowXML = "<set />\n" . $outflowXML;
						$generationXML = "<set />\n" . $generationXML;
						$count += 1;
					}
				}
				
				# Cap the sets
				$inflowXML = "<dataset seriesName='Inflow Meter' color='$INFLOW_COLOR'>\n" .
							 $inflowXML .
							 "</dataset>\n";
				$outflowXML = "<dataset seriesName='Outflow Meter' color='$OUTFLOW_COLOR'>\n" .
							  $outflowXML .
							  "</dataset>\n";
				$generationXML = "<dataset seriesName='Generation Meter' color='$GENERATION_COLOR'>\n" .
								 $generationXML .
								 "</dataset>\n";
				
				# Put the data into the XML
				if ($chartType == 'COMBINED') {
					$xml .= $inflowXML . $generationXML . $outflowXML;
				}
				else {
					$xml .= $inflowXML . $outflowXML . $generationXML;
				}
			}
		}
		else {
			$inflowPurchasedXML = '';
			$inflowMixedXML = '';
			$inflowFreeXML = '';
			$generationStoredXML = '';
			$query = "SELECT
					      inflowPurchased,
					      inflowMixed,
					      inflowFree,
					      generationStored
					  FROM
					  	  daily2linedata
					  WHERE
					      siteID = '$siteIdName'
					    AND
					      pointDate BETWEEN '$startDate' AND '$endDate'";
			
			$results = mysql_query ($query, $dbLink);
			
			$count = 0;
			$foundPurchased = 0;
			$foundMixed = 0;
			$foundFree = 0;
			$foundGeneration = 0;
			while ($row = mysql_fetch_array ($results)) {
				if ($row['inflowPurchased'] == '') {
					$inflowPurchasedXML .= "<set />\n";
				}
				else {
					$foundPurchased = 1;
					$inflowPurchasedXML .= "<set value='" . $row['inflowPurchased'] . "' />\n";
				}
				
				if ($row['inflowMixed'] == '') {
					$inflowMixedXML .= "<set />\n";
				}
				else {
					$foundMixed = 1;
					$inflowMixedXML .= "<set value='" . $row['inflowMixed'] . "' />\n";
				}
				
				if ($row['inflowFree'] == '') {
					$inflowFreeXML .= "<set />\n";
				}
				else {
					$foundFree = 1;
					$inflowFreeXML .= "<set value='" . $row['inflowFree'] . "' />\n";
				}
				
				if ($row['generationStored'] == '') {
					$generationStoredXML .= "<set />\n";
				}
				else {
					$foundGeneration = 1;
					$generationStoredXML .= "<set value='" . $row['generationStored'] . "' />\n";
				}
				
				$count += 1;
			}
			
			mysql_free_result ($results);
			
			# Check for empty data set
			if (!$foundPurchased && !$foundMixed && !$foundFree && !$foundGeneration) {
				exit;
			}
			
			# Make sure the datasets are full
			while ($count < $chartTypeToLengths[$chartType]) {
				$inflowPurchasedXML .= "<set />\n";
				$inflowMixedXML .= "<set />\n";
				$inflowFreeXML .= "<set />\n";
				$generationStored .= "<set />\n";
				$count += 1;
			}
			
			# Cap the sets
			$inflowPurchasedXML = "<dataset seriesName='Purchased Electricity' color='$INFLOW_PURCHASED_COLOR'>\n" .
						 		  $inflowPurchasedXML .
						 		  "</dataset>\n";
			$inflowMixedXML = "<dataset seriesName='Mixed Source Electricity' color='$INFLOW_MIXED_COLOR'>\n" .
						 	  $inflowMixedXML .
						 	  "</dataset>\n";
			$inflowFreeXML = "<dataset seriesName='Free Electricity' color='$INFLOW_FREE_COLOR'>\n" .
						 	 $inflowFreeXML .
						 	 "</dataset>\n";
			$generationStoredXML = "<dataset seriesName='Excess Electricity' color='$GENERATION_STORED_COLOR'>\n" .
						 		   $generationStoredXML .
						 		   "</dataset>\n";
			
			# Put the data into the XML
			$xml .= $inflowPurchasedXML . $inflowMixedXML . $inflowFreeXML . $generationStoredXML;
		}
	}
	else {
		$siteXML = array ();
		$siteCount = array ();
		
		foreach ($SITES_LIVE as $siteID => $enabled) {
			if (!$enabled) {
				continue;
			}
			
			$siteXML[$siteID] = '';
		}
		
		$query = "SELECT
					  siteID,
					  generation
				  FROM
					  " . $chartTypeToTable[$chartType] . "
				  WHERE
					  pointDate BETWEEN '$startDate' AND '$endDate'";
		
		$results = mysql_query ($query, $dbLink);
		
		$foundData = 0;
		while ($row = mysql_fetch_array ($results)) {
			if (!array_key_exists ($row['siteID'], $siteCount)) {
				$siteCount[$row['siteID']] = 0;
			}
			
			if ($row['generation'] == '') {
				$siteXML[$row['siteID']] .= "<set />\n";
			}
			else {
				$foundData = 1;
				$siteXML[$row['siteID']] .= "<set value='" . $row['generation'] . "' />\n";
			}
			
			$siteCount[$row['siteID']] += 1;
		}
		
		mysql_free_result ($results);
		
		# Check for empty data sets
		if (!$foundData) {
			exit;
		}
		
		# Make sure the datasets are full
		if ($siteCount['foodcoop'] > 0) {
			if ($chartType == 'DAILY') {
				while ($siteCount['foodcoop'] < $chartTypeToLengths[$chartType]) {
					foreach ($siteXML as $siteID => $sXML) {
						$siteXML[$siteID] = $sXML . "<set />\n";
					}
					$siteCount['foodcoop'] += 1;
				}
			}
			else {
				while ($siteCount['foodcoop'] < $chartTypeToLengths[$chartType]) {
					foreach ($siteXML as $siteID => $sXML) {
						$siteXML[$siteID] = "<set />\n" . $sXML;
					}
					$siteCount['foodcoop'] += 1;
				}
			}
			
			# Cap the sets and put into the XML
			foreach ($siteXML as $siteID => $sXML) {
				$xml .= "<dataset seriesName='" . $SITES[$siteID] . "'>\n" .
						$sXML .
						"</dataset>\n";
			}
		}
	}
	
	# See if style tags need to be included
    if ($bigStyle == 1) {
        $xml .= "<styles>\n";
        
        $xml .= "\t<definition>\n";
        $xml .= "\t\t<style name='LargeText' type='Font' font='Verdana' size='16' bold='1' color='000001' />\n";
        $xml .= "\t</definition>\n";
        
        $xml .= "\t<application>\n";
        $xml .= "\t\t<apply toObject='Caption' styles='LargeText' />\n";
        $xml .= "\t\t<apply toObject='DataLabels' styles='LargeText' />\n";
        $xml .= "\t\t<apply toObject='Legend' styles='LargeText' />\n";
        $xml .= "\t\t<apply toObject='VLineLabels' styles='LargeText' />\n";
        $xml .= "\t\t<apply toObject='XAxisName' styles='LargeText' />\n";
        $xml .= "\t\t<apply toObject='YAxisName' styles='LargeText' />\n";
        $xml .= "\t\t<apply toObject='YAxisValues' styles='LargeText' />\n";
        $xml .= "\t</application>\n";
        
        $xml .= "</styles>\n";
    }
    
    # Close the database
	mysql_close ($dbLink);
	
	# Need to add the document end tag
	$xml .= "</chart>";

	if ($xmlOut == 1) {
		echo $xml;
	}
	else {
		$xml = str_replace ("\n", "", $xml);
		$xml = str_replace ("\r", "", $xml);
		echo "{\"valid\":\"true\", \"xml\":\"" . $xml . "\"}";
	}
?>

<?php
	function generate_category_section ($chartType, $now, $sunrise, $localNoon, $sunset) {
		global $DAILY_AVERAGE, $INFLOW_COLOR, $OUTFLOW_COLOR, 
	       	   $GENERATION_COLOR, $TIMEOFFSET, $INFLOW_PURCHASED_COLOR,
	       	   $INFLOW_MIXED_COLOR, $INFLOW_FREE_COLOR, $GENERATION_STORED_COLOR;
        
		# Start with the base tag
		$xml = "<chart animation='0' " .
			   "bgColor='FFFFFF' " .
			   "caption='REPLACEME' " .
			   "chartLeftMargin='0' " .
			   "chartRightMargin='5' " .
			   "decimals='1' " .
			   "drawAnchors='0' " .
			   "forceDecimals='1' " .
			   "formatNumber='1' " .
			   "formatNumberScale='0' " .
			   "labelDisplay='WRAP' " .
			   "numDivLines='5' " .
			   "rotateLabels='0' " .
			   "rotateYAxisName='1' " .
			   "showAnchors='0' " .
			   "showBorder='0' " .
			   "showNames='0' " .
			   "showValues='0' " .
			   "showZeroPlane='1' " .
			   "useRoundEdges='1' ";
	   
		if ($chartType == 'DAILY' || $chartType == 'DAILY2LINE') {
			$xml .= "yAxisMaxValue='200' ";
		}
		else {
			$xml .= "yAxisMaxValue='1' ";
		}
			   
		$xml .= "yAxisMinValue='0' ";
		
		if ($chartType == 'DAILY' || $chartType == 'DAILY2LINE') {
			$xml .= "yAxisName='Watts'>\n";
		}
		else if ($chartType == 'COMBINED') {
			$xml .= "yAxisName='Kilowatt Hours'>\n";
		}
		else {
			$xml .= "yAxisName='Kilowatts'>\n";
		}
		
		# Create the categories tag
		$xml .= "<categories>\n";
		
		# Create the category information
		if ($chartType == 'DAILY' || $chartType == 'DAILY2LINE') {
			$day = $now['mday'];
			$month = $now['mon'];
			$year = $now['year'];
			$hour = "0";
			$minute = "0";
			
			while ($hour < 24) {
				$catName = "";
				$showName = 0;
				
				if ($minute == 0) {
					if ($hour == 0) {
						$catName = "Midnight";
					}
					elseif ($hour == 12) {
						$catName = "Noon";
					}
					elseif ($hour > 12) {
						$catName = ($hour - 12) . ":00";
					}
					elseif ($hour < 10) {
						$catName = substr ($hour, 0, 1) . ":00";
					}
					else {
						$catName = "$hour:00";
					}
					
					if (($hour % 3) == 0 || ($hour == 23 && $minute == 55)) {
						$showName = 1;
					}
				}
				else {
					$catName = "$hour:$minute";
				}
				
				# Check for the right time to insert an atmosphere data marker if
				# it is ok to do that
				if ($sunset != '') {
					if ($sunrise != '' && (($minute >= $sunrise['minutes'] && $hour == $sunrise['hours']) ||
						($minute == 0 && $hour == ($sunrise['hours'] + 1)))) {
						$m = $sunrise['minutes'];
						if ($m < 10) {
							$m = "0$m";
						}
						$xml .= "<vLine label='Sunrise - ". $sunrise['hours'] . ":$m' thickness='2' labelPosition='.03' />\n";
						$sunrise = '';
					}
					elseif ($localNoon != '' && (($minute >= $localNoon['minutes'] && $hour == $localNoon['hours']) ||
							($minute == 0 && $hour == ($localNoon['hours'] + 1)))) {
						$m = $localNoon['minutes'];
						if ($m < 10) {
							$m = "0$m";
						}
						$h = ($localNoon['hours'] % 12 == 0) ? '12' : $localNoon['hours'] % 12;
						$xml .= "<vLine label='Local Noon - $h:$m' thickness='2' labelPosition='.03' />\n";
						$localNoon = '';
					}
					elseif ($sunset != '' && (($minute >= $sunset['minutes'] && $hour == $sunset['hours']) ||
							($minute == 0 && $hour == ($sunset['hours'] + 1)))) {
						$m = $sunset['minutes'];
						if ($m < 10) {
							$m = "0$m";
						}
						$h = $sunset['hours'] % 12;
						$xml .= "<vLine label='Sunset - $h:$m' thickness='2' labelPosition='.03' />\n";
						$sunset = '';
					}
		
					if ($sunrise == '' && $localNoon == '' &&
						$sunset == '') {
						$writeAtmosphereData = 0;
					}
				}
				if ($showName) {
					$xml .= "<category label='$catName' showName='1' />\n";
				}
				else {
					$xml .= "<category label='' />\n";
				}
				$minute += 5;
				
				if ($minute >= 60) {
					$minute = "0";
					$hour += 1;
				}
			}
		}
		elseif ($chartType == 'WEEKLY') {
			# For this chart, we need to create a list of 7 days, each day having
			# 24 data points (hours), but we only want to display the day label at
			# the beginning of the 24 hours
			$currTime = getdate ();
			$currTime['hours'] += $TIMEOFFSET;
			$currTime = getdate (mktime ($currTime['hours'], $currTime['minutes'], $currTime['seconds'], $currTime['mon'], $currTime['mday'], $currTime['year']));
			$hour = $startHour;
			$minute = 0;
			$weekday = $currTime['wday'];
	
			if ($hour == "") {
				$hour = $currTime['hours'];
				$startHour = $currTime['hours'];
			}
			
			$categoryCount = 0;
			while ($categoryCount < 168) {
				if ($hour == 0 && $minute == 0 && $categoryCount > 0) {
					$xml .= "<category label='|' showName='1' />\n";
				}
				elseif ($hour == 12 && $minute == 0) {
					$strWeekday = get_weekday ($weekday);
					$xml .= "<category label='$strWeekday' showName='1' />\n";
				}
				else {
					$xml .= "<category label='' />\n";
				}
				
				$categoryCount += 1;
				$hour += 1;
				
				if ($hour == 24) {
					$hour = 0;
					$weekday = ($weekday + 1) % 7;
				}
			}
		}
		elseif ($chartType == 'YEARLY') {
			# For this chart, we need to create a list of how every many days
			# happen to be in the current month (30ish data points).
			$currTime = getDate ();
			$currTime['hours'] += $TIMEOFFSET;
			$currTime['mday'] -= 1;
			$endTime = getdate (mktime ($currTime['hours'], $currTime['minutes'], $currTime['seconds'], $currTime['mon'], $currTime['mday'] + 1, $currTime['year']));
			$currTime = getdate (mktime ($currTime['hours'], $currTime['minutes'], $currTime['seconds'], $currTime['mon'], $currTime['mday'] + 1, $currTime['year'] - 1));
			
			while (($endTime['mon'] != $currTime['mon']) || ($endTime['year'] != $currTime['year']) || ($endTime['mday'] != $currTime['mday'])) {
				if ($currTime['mday'] == 1) {
					$xml .= "<category label='|' showLabel='1' />\n";
				}
				elseif ($currTime['mday'] == 15) {
					$xml .= "<category label='" . get_month ($currTime['mon']) . "' showName='1' />\n";
				}
				else {
					$xml .= "<category label='' />\n";
				}
				
				$currTime['mday'] += 1;
				$currTime = getdate (mktime ($currTime['hours'], $currTime['minutes'], $currTime['seconds'], $currTime['mon'], $currTime['mday'], $currTime['year']));
			}
		}
		elseif ($chartType == 'COMBINED') {
			$currTime = getDate ();
			$currTime['hours'] += $TIMEOFFSET;
			$currTime = getdate (mktime ($currTime['hours'], $currTime['minutes'], $currTime['seconds'], $currTime['mon'], $currTime['mday'], $currTime['year']));
			$xml .= "<category label='" . get_month ($currTime['mon']) . "' />\n";
			
			$dayTime = getDate ();
			$dayTime['hours'] += $TIMEOFFSET;
			$dayTime = getDate (mktime (0, 0, 0, $dayTime['mon'] + 1, 1, $dayTime['year'] - 1));
			
			while ($dayTime['mon'] != $currTime['mon'] || $dayTime['year'] != $currTime['year']) {
				$xml .= "<category label='" . get_month ($dayTime['mon']) . "' />\n";
				$dayTime = getDate (mktime (0, 0, 0, $dayTime['mon'] + 1, 1, $dayTime['year']));
			}
		}
		
		# Add the ending category tag
		$xml .= "</categories>";
		return $xml;
	}
?>