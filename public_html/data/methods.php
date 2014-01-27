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
	
    $sunrise = '';
    $localNoon = '';
    $sunset = '';
    $weatherImgUrl = '';
    $temperature = '';
    
    # Get the weekday string for a number
    # The days are numbered 0 to 6 starting with Sunday
    function get_weekday ($dayNumber) {
        if ($dayNumber == 0) {
            return "Sun";
        }
        else if ($dayNumber == 1) {
            return "Mon";
        }
        else if ($dayNumber == 2) {
            return "Tue";
        }
        else if ($dayNumber == 3) {
            return "Wed";
        }
        else if ($dayNumber == 4) {
            return "Thu";
        }
        else if ($dayNumber == 5) {
            return "Fri";
        }
        else if ($dayNumber == 6) {
            return "Sat";
        }
        else {
            return "Unknown Weekday";  // To let you know you are an idiot
        }
    }
    
    # Get the month string for a number
    # The months are numbered 0 to 11 starting with January
    function get_month ($monthNumber) {
        if ($monthNumber == 1) {
            return "Jan";
        }
        elseif ($monthNumber == 2) {
            return "Feb";
        }
        elseif ($monthNumber == 3) {
            return "Mar";
        }
        elseif ($monthNumber == 4) {
            return "Apr";
        }
        elseif ($monthNumber == 5) {
            return "May";
        }
        elseif ($monthNumber == 6) {
            return "Jun";
        }
        elseif ($monthNumber == 7) {
            return "Jul";
        }
        elseif ($monthNumber == 8) {
            return "Aug";
        }
        elseif ($monthNumber == 9) {
            return "Sep";
        }
        elseif ($monthNumber == 10) {
            return "Oct";
        }
        elseif ($monthNumber == 11) {
            return "Nov";
        }
        elseif ($monthNumber == 12 || $monthNumber == 0) {
            return "Dec";
        }
        else {
            return "Unknown Month";  // To let you know you are an idiot
        }
    }
    
    # Get the number of days in the given month
    # The months are numbered 0 to 11 starting with January
    # You only need to provide the year if you want February's day count
    function get_day_count ($monthNumber, $year=0) {
        if ($monthNumber == 1 || $monthNumber == 3 || $monthNumber == 5 ||
            $monthNumber == 7 || $monthNumber == 8 || $monthNumber == 10 ||
            $monthNumber == 12) {
            return 31;
        }
        elseif ($monthNumber == 4 || $monthNumber == 6 || $monthNumber == 9 ||
                $monthNumber == 11) {
            return 30;
        }
        else if ($monthNumber == 2) {
            # This is February, have to determine leap year
            if (($year % 4 == 0) && ($year % 400 > 0)) {
                return 29;  # Leap year
            }
            else {
                return 28;
            }
        }
        else {
            return 0; // To let you know you are an idiot
        }
    }
    
    function validate_year_month_day ($year, $month, $day) {
        if ($month == 1 || $month == 3 || $month == 5 ||
            $month == 6 || $month == 8 || $month == 10 ||
            $month == 12) {
            if ($day > 31) {
                $day = 1;
                $month += 1;
            }
        }
        elseif ($month == 2 && $day > 28) {
            if (($year % 4) == 0 && ($year % 400) != 0) {
                if ($day == 30) {
                    $day += 1;
                }
                else {
                    $day = 1;
                    $month += 1;
                }
            }
            elseif ($day == 29) {
                $day = 1;
                $month += 1;
            }
        }
        elseif ($day > 30) {
            $day = 1;
            $month += 1;
        }
        
        if ($month > 12) {
            $year += 1;
            $month = 1;
        }
        
        return array ($year, $month, $day);
    }
    
    function parse_weatherData () {
        # Set some variables as globals
        global $LOCATION_ZIP, $sunrise, $sunset, $weatherImgUrl, $temperature;
        
        # Open the stream
        $fp = fopen ("http://weather.yahooapis.com/forecastrss?p=$LOCATION_ZIP", "r") or die ("Cannot read RSS data file.");
        
        # Do the parsing
        $data = fread ($fp, 1000000);
        $data = preg_split ("/\<img/", $data);
        
        $data2 = $data[0];
        $data2 = preg_split ("/yweather:condition/", $data2);
        $temp = preg_split ("/temp=/", $data2[1]);
        $temp = preg_split ('/"/', $temp[1]);
        $temperature = $temp[1];
        
        $imgURL = $data[1];
        $imgURL = preg_split ('/"/', $imgURL);
        $weatherImgUrl = $imgURL[1];
        
        $sun = preg_split ("/yweather:astronomy/", $data2[0]);
        $sun = preg_split ('/"/', $sun[1]);
        $sunrise = $sun[1];
        $sunset = $sun[3];
    }
    
    function get_sunrise_sunset () {
        # Set some variables as globals
        global $siteIdName, $TIMEOFFSET, $SUNRISE_SUNSET_DONE, $sunrise, $localNoon, $sunset,
        	   $DB_SERVER, $DB_DATABASE, $DB_USERNAME, $DB_PASSWORD;
        
        # Parse the weather data
        parse_weatherData ();
        
        # Put together datetimes
        $todayDayArr = getdate ();
        $todayDayArr['hours'] += $TIMEOFFSET;
        $todayDayArrTS = mktime ($todayDayArr['hours'], $todayDayArr['minutes'], $todayDayArr['seconds'],
        						 $todayDayArr['mon'], $todayDayArr['mday'], $todayDayArr['year']);
        $localNoon = (((substr ($sunset, 0, 1) * 60 + 720) + substr ($sunset, 2, 2)) + ((substr ($sunrise, 0, 1) * 60) + substr ($sunrise, 2, 2))) / 2;
        $localNoonHours = floor ($localNoon / 60);
        $localNoonMinutes = ($localNoon % 60);
        $sunriseTS = mktime (substr ($sunrise, 0, 1), substr ($sunrise, 2, 2), 0, $todayDayArr['mon'], $todayDayArr['mday'], $todayDayArr['year']);
        $sunrise = getdate ($sunriseTS);
        $localNoonTS = mktime ($localNoonHours, $localNoonMinutes, 0, $todayDayArr['mon'], $todayDayArr['mday'], $todayDayArr['year']);
        $localNoon = getdate ($localNoonTS);
        $sunsetTS = mktime ((substr ($sunset, 0, 1) + 12), substr ($sunset, 2, 2), 0, $todayDayArr['mon'], $todayDayArr['mday'], $todayDayArr['year']);
        $sunset = getdate ($sunsetTS);
        
        # Mark that we have done this
        $SUNRISE_SUNSET_DONE = 1;
        
        # Send the data to storage email
        $to = 'solar.ypsi.analytics@gmail.com';
        $subject = "$siteIdName ATMOSPHERE";
        $message = "Sunrise: " . $sunrise['hours'] . ":" . $sunrise['minutes'] . "<br>\n " .
                   "Local Noon: " . $localNoon['hours'] . ":" . $localNoon['minutes'] . "<br>\n" .
                   "Sunet: " . $sunset['hours'] . ":" . $sunset['minutes'] . "<br>\n";
        $message = substr ($message, 0, strlen ($message) - 2);
        $headers = "From: solar.ypsi.analytics@gmail.com\r\nReply-To: solar.ypsi.analytics@gmail.com";
        $mail_sent = @mail( $to, $subject, $message, $headers );
        
        # Store the times in the database
        $dbLink = mysql_connect ($DB_SERVER, $DB_USERNAME, $DB_PASSWORD);
        mysql_select_db ($DB_DATABASE, $dbLink);
        
        $query = "INSERT IGNORE INTO
        		      atmospheredata
        		  (
        		      siteID,
        		      date,
        		      sunrise,
        		      localNoon,
        		      sunset
        		  )
        		  VALUES
        		  (
        		      '$siteIdName',
        		      '" . date ("Y-m-d", $todayDayArrTS) . "',
        		      '" . date ("Y-m-d H:i:s", $sunriseTS) . "',
        		      '" . date ("Y-m-d H:i:s", $localNoonTS) . "',
        		      '" . date ("Y-m-d H:i:s", $sunsetTS) . "'
        		  )";
    	
    	mysql_query ($query, $dbLink);
    	mysql_close ($dbLink);
    }
    
    function get_weather () {
        # Parse the weather data
        parse_weatherData ();
    }
    
    function calculate_pie_charts () {
    	global $SITES_MAX_KW, $SITES_IN_YPSI;
    	
    	$total_sites = 0;
    	$sites_in_ypsi = 0;
    	$total_kw = 0;
    	$kw_in_ypsi = 0;
    	foreach ($SITES_IN_YPSI as $site_id => $in_ypsi) {
    		$total_sites += 1;
    		$total_kw += $SITES_MAX_KW[$site_id];
    		
    		if ($in_ypsi) {
    			$sites_in_ypsi += 1;
    			$kw_in_ypsi += $SITES_MAX_KW[$site_id];
    		}
    	}
    	
    	$sites_in_ypsi_perc = ($sites_in_ypsi / $total_sites) * 100;
    	$kw_in_ypsi_perc = ($kw_in_ypsi / $total_kw) * 100;
    	
    	$total_w_label = 'kW';
    	$ypsi_w_label = 'kW';
    	if ($total_kw > 1000) {
    		$total_w_label = 'MW';
    		$total_kw = $total_kw / 1000;
    	}
    	if ($kw_in_ypsi > 1000) {
    		$ypsi_w_label = 'MW';
    		$kw_in_ypsi = $kw_in_ypsi / 1000;
    	}
    	
    	return array (
    		'total_sites' => $total_sites,
    		'sites_in_ypsi' => $sites_in_ypsi,
    		'sites_in_ypsi_perc' => $sites_in_ypsi_perc,
    		'sites_other_perc' => 100 - $sites_in_ypsi_perc,
    		'total_kw' => $total_kw,
    		'kw_in_ypsi' => $kw_in_ypsi,
    		'kw_in_ypsi_perc' => $kw_in_ypsi_perc,
    		'kw_other_perc' => 100 - $kw_in_ypsi_perc,
    		'total_w_label' => $total_w_label,
    		'ypsi_w_label' => $ypsi_w_label,
    	);
    }