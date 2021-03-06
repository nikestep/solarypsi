<?php
$local_tz = new DateTimeZone('America/New_York');
$nullz = null;

// Track success of portions
$success = True;

// Get all enphase systems
$systems = Array ();
if ($stmt_inner = $db_link->prepare ("SELECT " .
                                     "    system_id, " .
                                     "    user_id " .
                                     "FROM " .
                                     "    enphase_system " .
                                     "GROUP BY " .
                                     "    system_id, " .
                                     "    user_id")) {
    if (!$stmt_inner->execute ()) {
        $job_result = 'Error';
        $job_msg = '20 ' . $db_link->error;
        $success = False;
    }
    else {
        $stmt_inner->bind_result ($system_id, $user_id);
        while ($stmt_inner->fetch ()) {
            $systems[$system_id] = $user_id;
        }
    }
    $stmt_inner->close ();
}
else {
    $job_result = 'Error';
    $job_msg = '33 ' . $db_link->error;
    $success = false;
}

// Get data for each system
if ($success) {
    foreach ($systems as $system_id => $user_id) {
        // Set the date
        $curr_interval = new DateTime (null, $local_tz);
        $curr_interval->setTime (0, 0);
        $date_str = $curr_interval->format ('Y-m-d');
        $year_str = $curr_interval->format ('Y');
        $month_str = $curr_interval->format ('m');

        // Retrieve the data from the internets and parse it
        $url = 'https://api.enphaseenergy.com/api/v2/systems/' . $system_id . '/summary?' .
               'summary_date=' . $date_str . '&key=' . $ENPHASE_API_KEY .
			   '&user_id=' . $user_id;
        $str = file_get_contents ($url);
        $json = json_decode ($str, TRUE);

        // Verify there is data to parse
        if (array_key_exists ('reason', $json)) {
            // Log this, but don't fail the whole job
            $job_result = 'Error';
            $job_msg = 'Reason: ' . $json['message'][0];
            continue;
        }

        // Get the sites that need updated with this data
        if ($success &&
            $stmt_inner = $db_link->prepare ("SELECT " .
                                             "    site_id, " .
                                             "    num_units " .
                                             "FROM " .
                                             "    enphase_system " .
                                             "WHERE " .
                                             "    system_id = ?")) {
            $stmt_inner->bind_param ('s', $system_id);
            $stmt_inner->execute ();
            $stmt_inner->bind_result ($site_id, $num_units);

            $sites = Array ();
            while ($stmt_inner->fetch ()) {
                $sites[$site_id] = $num_units;
            }

            $stmt_inner->close ();

            // Update the data for each site
            foreach ($sites as $site_id => $num_units) {
                // Load the data point for today
                if ($success &&
                    $stmt_inner = $db_link->prepare ("INSERT INTO " .
                                                     "    data_yearly " .
                                                     "VALUES " .
                                                     "    (?, ?, ?, ?, ?, ?, ?)")) {
                    // Check if value needs scaled
                    $enwh = $json['energy_today'];
                    if ($num_units != 1 && $enwh > 0 && $json['modules'] > 0) {
                        $enwh *= ($num_units / $json['modules']);
                    }
                
                    $stmt_inner->bind_param ('ssiiiii', $site_id,
                                                        $date_str,
                                                        $year_str,
                                                        $month_str,
                                                        $nullz,
                                                        $nullz,
                                                        $enwh);
                    if (!$stmt_inner->execute ()) {
                        $job_result = 'Error';
                        $job_msg = '105 ' . $db_link->error;
                        $success = false;
                        break;
                    }
                    $stmt_inner->close ();
                }
                else if ($success) {
                    $job_result = 'Error';
                    $job_msg = '113 ' . $db_link->error;
                    $success = false;
                    break;
                }
            }
        }
        else if ($success) {
            $job_result = 'Error';
            $job_msg = '121 ' . $system_id . ' ' . $db_link->error;
            $success = false;
            break;
        }

        // Sleep for three seconds before we request the next set of data
        // (try not to hit the enphase server too quickly)
        sleep (3);
    }
}

// Mark successful
if ($success) {
    $job_result = 'Success';
}
?>