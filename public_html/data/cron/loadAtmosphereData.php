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
	include ('../methods.php');
	
	function error_function ($error_level,$error_message, $error_file,$error_line,$error_context) {
		global $FROM_EMAIL, $ALERT_EMAIL, $SEND_ALERT_EMAILS;
		
		if ($error_level == 2048 || (strpos ($error_message, 'Undefined') >= 0 && strpos ($error_message, 'chartType') >= 0)) {
			return;
		}
		
		# Send the error by email
	    $to = $ALERT_EMAIL;
	    $subject = "Clear Points ERROR";
	    $message = "Error Level: $error_level\n" .
	    		   "Error Message: $error_message\n" .
	    		   "Error File: $error_file\n" .
	    		   "Error Line: $error_line\n";
	    $headers = "From: $FROM_EMAIL\r\nReply-To: $FROM_EMAIL";
	    ($SEND_ALERT_EMAILS ? $mail_sent = @mail ($to, $subject, $message, $headers) : '');
	}
	set_error_handler ("error_function");
	
	foreach ($SITES_LIVE as $siteIdName => $enabled) {
		if (!$enabled) {
			continue;
		}
		
		get_sunrise_sunset ();
	}
?>