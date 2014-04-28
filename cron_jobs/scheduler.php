<?php
// Define helper function for checking time components
function component_matches ($comp, $time_elem) {
    $time_elem = intval ($time_elem);
    if ($comp === '*') {
        return true;
    }
    
    if (!is_numeric ($comp)) {
        if (strpos ($comp, ',') !== false) {
            $parts = explode (',', $comp);
            foreach ($parts as $part) {
                if (intval($part) === $time_elem) {
                    return true;
                }
            }
        }
        else if (strpos ($comp, '-') !== false) {
            $parts = explode ('-', $comp);
            foreach (range(intval($parts[0]), intval($parts[1])) as $part) {
                if (intval($part) === $time_elem) {
                    return true;
                }
            }
        }
        else if (strpos ($comp, '/') !== false) {
            $parts = explode ('/', $comp);
            if (($time_elem % intval ($parts[1])) === 0) {
                return true;
            }
        }
        
        return false;
    }
    else if (intval ($comp) !== $time_elem) {
        return false;
    }
    
    return true;
}

// Define function to determine if job should be run now
function run_now ($schedule) {
    // Tokenize the schedule string
    $token = explode (' ', $schedule);
    
    // Get the current time
    $now = getdate();
    
    // Check components
    if (!component_matches ($token[0], $now['minutes'])) return false;
    if (!component_matches ($token[1], $now['hours'])) return false;
    if (!component_matches ($token[2], $now['mday'])) return false;
    if (!component_matches ($token[3], $now['mon'])) return false;
    
    // Assume we can run it at this point
    return true;
}

// Include the configuration file
include ('/home/solaryps/config/config.php');

// Open connection to MySQL database
$db_link = new mysqli ($DB_SERVER, $DB_USERNAME, $DB_PASSWORD, $DB_DATABASE, $DB_PORT, $DB_SOCKET);

// Retrieve all of the scheduled jobs
$stmt = $db_link->prepare ("SELECT " .
                           "    name, " .
                           "    path, " .
                           "    schedule, " .
                           "    error_email " .
                           "FROM " .
                           "    cron_schedule " .
                           "WHERE " .
                           "    enabled = 1");
$stmt->execute ();
$stmt->store_result ();
$stmt->bind_result ($name,
                    $path,
                    $schedule,
                    $error_email);

while ($stmt->fetch ()) {
    if (run_now ($schedule)) {
        // Run the job
        $job_result = '';
        $job_msg = '';
        include ($path);
        
        // Log the result
        if ($stmt_log = $db_link->prepare ("INSERT INTO " .
                                           "    cron_log " .
                                           "( " .
                                           "    name, " .
                                           "    result, " .
                                           "    message " .
                                           ") " .
                                           "VALUES " .
                                           "( " .
                                           "    ?, " .
                                           "    ?, " .
                                           "    ? " .
                                           ")")) {
            $stmt_log->bind_param ('sss', $name, $job_result, $job_msg);
            if (!$stmt_log->execute ()) {
                error_log ($db_link->error);
            }
            $stmt_log->close ();
        }
        else {
            error_log ($db_link->error);
        }
        
        // Send an error email if failed and requested
        if ($job_result === 'Error' && $error_email !== null) {
            error_log ('Should send email');
        }
    }
}

$stmt->close ();

// Close the database connection
$db_link->close ();
?>