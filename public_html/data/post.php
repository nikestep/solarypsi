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
	
	# Include the config and method files
    include ('config.php');
    include ('methods.php');
    
	function error_function ($error_level,$error_message, $error_file,$error_line,$error_context) {
		global $FROM_EMAIL, $ALERT_EMAIL, $SEND_ALERT_EMAILS;
		
		if ($error_level == 2048 || (strpos ($error_message, 'Undefined') >= 0 && strpos ($error_message, 'chartType') >= 0)) {
			return;
		}
		
		# Send the error by email
	    $to = $ALERT_EMAIL;
	    $subject = "Post ERROR";
	    $message = "Error Level: $error_level\n" .
	    		   "Error Message: $error_message\n" .
	    		   "Error File: $error_file\n" .
	    		   "Error Line: $error_line\n";
	    $headers = "From: $FROM_EMAIL\r\nReply-To: $FROM_EMAIL";
	    ($SEND_ALERT_EMAILS ? $mail_sent = @mail ($to, $subject, $message, $headers) : '');
	}
	set_error_handler ("error_function");
    
    # Check that we got all the required parameters
    if (!isset ($_REQUEST['siteIdName']) ||
        !isset ($_REQUEST['inflowCount']) ||
        !isset ($_REQUEST['outflowCount']) ||
        !isset ($_REQUEST['generationCount'])) {
        die ("Not All Required Parameters Were Supplied.");
    }
    
    # Get values from the request
    $siteIdName = $_REQUEST['siteIdName'];
    $inflowCount = $_REQUEST['inflowCount'];
    $outflowCount = $_REQUEST['outflowCount'];
    $generationCount = $_REQUEST['generationCount'];
    
    # Check that we recognize the site as valid
    if (!key_exists ($siteIdName, $SITES_LIVE) ||
        $SITES_LIVE[$siteIdName] == false) {
        die ("SiteIDName Not Recognized as Live or Valid.");
    }
    
    # Create time of now
    $now = getdate ();
    $now['hours'] += $TIMEOFFSET;
    $now = getdate (mktime ($now['hours'], $now['minutes'], $now['seconds'], $now['mon'], $now['mday'], $now['year']));
    $nowStr = date ("Y-m-d H:i:s", mktime ($now['hours'], $now['minutes'], $now['seconds'], $now['mon'], $now['mday'], $now['year']));
    
    # Connect to the database
    $dbLink = mysql_connect ($DB_SERVER, $DB_USERNAME, $DB_PASSWORD);
    mysql_select_db ($DB_DATABASE, $dbLink);
    
    # Store posted point
    $query = "INSERT INTO 
    		      point
    		  VALUES (
    		      '$siteIdName',
    		      '$nowStr',
    		      $inflowCount,
    		      $outflowCount,
    		      $generationCount
    		  )";
    #mysql_query ($query, $dbLink);
    
    # Let the database know we had a hit from here
    $query = "UPDATE
    		      site
    		  SET
    		      lastContact = '$nowStr',
    		      absenceReported = 0
    		  WHERE
    		      ID = '$siteIdName'";
	#mysql_query ($query, $dbLink);
    
    # Update the totals
    $query = "UPDATE
    		      total
    		  SET
    		      dailyInflow = dailyInflow + $inflowCount,
    		  	  dailyOutflow = dailyOutflow + $outflowCount,
    		  	  dailyGeneration = dailyGeneration + $generationCount,
    		  	  weeklyInflow = weeklyInflow + $inflowCount,
    		  	  weeklyOutflow = weeklyOutflow + $outflowCount,
    		  	  weeklyGeneration = weeklyGeneration + $generationCount,
    		  	  yearlyInflow = yearlyInflow + $inflowCount,
    		  	  yearlyOutflow = yearlyOutflow + $outflowCount,
    		  	  yearlyGeneration = yearlyGeneration + $generationCount,
    		  	  monthlyInflow = monthlyInflow + $inflowCount,
    		  	  monthlyOutflow = monthlyOutflow + $outflowCount,
    		  	  monthlyGeneration = monthlyGeneration + $generationCount
    		  WHERE
    		      siteID = '$siteIdName'";
    #mysql_query ($query, $dbLink);
    
    # Close the database link
    mysql_close ($dbLink);
?>