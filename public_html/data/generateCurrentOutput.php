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
	
    # Include config file
    include ('config.php');
    
    # Create an array to avoid duplicate entries
    $siteSeen = array ();
    $siteSum = array ();
    foreach ($SITES_LIVE as $siteID => $enabled) {
    	if (!$enabled) {
    		continue;
    	}
    	
    	$siteSeen[$siteID] = 0;
    	$siteSum[$siteID] = 0;
    }
    
    # Go back in time two minutes
    $date = getdate ();
    $date['hours'] += $TIMEOFFSET;
    $date = getdate (mktime ($date['hours'], $date['minutes'] - 2, $date['seconds'],
                             $date['mon'], $date['mday'], $date['year']));
    $cutoffTime = $date['year'] . '-' . $date['mon'] . '-' . $date['mday'] . ' ' .
    			  $date['hours'] . ':' . $date['minutes'] . ':' . $date['seconds'];
    
    # Connect to the database
    $dbLink = mysql_connect ($DB_SERVER, $DB_USERNAME, $DB_PASSWORD);
    mysql_select_db ($DB_DATABASE, $dbLink);
    
    # Run the query
    $query = "SELECT
    			  siteID,
    		      generation
    		  FROM
    		      point
    		  WHERE
    		  	  pointDate >= '$cutoffTime'
    		  ORDER BY
    		      pointDate DESC";
    $result = mysql_query ($query, $dbLink);
    
    $generationTotal = 0;
    while ($row = mysql_fetch_array ($result)) {
    	if ($siteSeen[$row['siteID']] < 2) {
    		$siteSum[$row['siteID']] += (((int) $row['generation']) * $SITES_LINES_FACTORS[$row['siteID']]['Generation'] * $watt_constant);
    		$siteSeen[$row['siteID']] += 1;
    	}
    }
    
    mysql_free_result ($result);
    
    # Close the database
	mysql_close ($dbLink);
	
	# Average the sums for each site
	$totalSum = 0;
	foreach ($siteSum AS $siteID => $sum) {
		$siteSum[$siteID] = $sum / 2;
		$totalSum += $siteSum[$siteID];
	}
	
	# Get the maximum number of watt hours
	$wFactor = $MAX_KILOWATTS * 100;
	if (isset ($_REQUEST['siteIdName'])) {
		$wFactor = $SITES_MAX_WH[$_REQUEST['siteIdName']] * $watt_constant;
	}
	
	# Format the label for the dial and the percentage that the dial should
	# be set at
	$label = number_format ($totalSum, 0);
	$sum = number_format (($totalSum / $wFactor) * 100, 0);

	# If we have a label that is bigger than the maximum possible, reset it
	# to the maximum possible
	if ($label > $wFactor) {
		$label = $wFactor;
	}

	# Add the unit of measure to the label
	$label .= " W";
    #"&chl=$label";
    
    $json = "{\"meterURL\": " .
    		" \"http://chart.apis.google.com/chart?cht=gom&chs=185x100&chd=t:$sum&chco=666666,FFFF00&chf=bg,s,FFFFFF00\", ".
    		" \"numeric\": \"$sum\", ".
    		" \"label\": \"$label\"}";
    
    # Write URL to response
    header ('Content-type: text/json');
    if (isset ($_REQUEST['callback'])) {
    	echo $_REQUEST['callback'] . "($json)";
    }
    else {
    	echo $json;
    }
?>