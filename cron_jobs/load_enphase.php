<?php
$local_tz = new DateTimeZone('America/New_York');

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
        $job_msg = '19 ' . $db_link->error;
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
    $job_msg = '32 ' . $db_link->error;
    $success = false;
}

// Get data for each system
if ($success) {
    foreach ($systems as $system_id => $user_id) {
        // Set the date
        $curr_interval = new DateTime(null, $local_tz);
		$curr_interval->setTime(0, 0);
		$date_str = $curr_interval->format('Y-m-d');

        // Retrieve the data from the internets and parse it
        $url = 'https://api.enphaseenergy.com/api/v2/systems/' . $system_id . '/stats?' .
               'key=' . $ENPHASE_API_KEY . '&user_id=' . $user_id;
        $str = file_get_contents ($url);
        $json = json_decode ($str, TRUE);

        // Verify there is data to parse
        if (array_key_exists ('reason', $json)) {
            // Log this, but don't fail the whole job
            $job_result = 'Error';
            $job_msg = 'Reason: ' . $json['message'][0];
            continue;
        }
        else if (count ($json['intervals']) === 0) {
            continue;
        }

        // Parse each point
        $points = Array ();
        $indx = 0;
        foreach ($json['intervals'] as $point) {
            $time = new DateTime('@' . $point['end_at']);

            while ($curr_interval < $time) {
                // Populate this interval with zeros
                $points[$indx] = Array('inflow' => null, 'outflow' => null, 'generation' => 0,
                                       'inflow_purchased' => null, 'inflow_mixed' => null, 'inflow_free' => 0);

                // Go to the next interval
                $curr_interval->add (date_interval_create_from_date_string ('5 minutes'));
                $indx += 5;
            }

            $points[$indx] = Array ('inflow' => null, 'outflow' => null, 'generation' => $point['enwh'],
                                    'inflow_purchased' => null, 'inflow_mixed' => null, 'inflow_free' => 0,
                                    'devices_reporting' => $point['devices_reporting']);
            $curr_interval->add (date_interval_create_from_date_string ('5 minutes'));
            $indx += 5;
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
                // Delete the current day's data points for this system
                if ($success &&
                    $stmt_inner = $db_link->prepare ("DELETE FROM " .
                                                     "    data_daily " .
                                                     "WHERE " .
                                                     "    site_id = ? " .
                                                     "  AND " .
                                                     "    point_date = ?")) {
                    $stmt_inner->bind_param ('ss', $site_id, $date_str);
                    if (!$stmt_inner->execute ()) {
                        $job_result = 'Error';
                        $job_msg = '118 ' . $db_link->error;
                        $success = False;
                        break;
                    }
                    $stmt_inner->close ();
                }
                else if ($success) {
                    $job_result = 'Error';
                    $job_msg = '126 ' . $db_link->error;
                    $success = false;
                    break;
                }

                // Load the data points back
                if ($success &&
                    $stmt_inner = $db_link->prepare ("INSERT INTO " .
                                                     "    data_daily " .
                                                     "VALUES " .
                                                     "    (?, ?, ?, ?, ?, ?, ?, ?, ?)")) {
                    foreach ($points as $idx => $vals) {
                        // Check if value needs scaled
                        $enwh = $vals['generation'];
                        if ($num_units != 1 && $enwh > 0 && $vals['devices_reporting'] > 0) {
                            $enwh *= ($num_units / $vals['devices_reporting']);
                        }
                        
                        $stmt_inner->bind_param ('ssiiiiiii', $site_id,
                                                              $date_str,
                                                              $idx,
                                                              $vals['inflow'],
                                                              $vals['outflow'],
                                                              $enwh,
                                                              $vals['inflow_purchased'],
                                                              $vals['inflow_mixed'],
                                                              $vals['inflow_free']);
                        if (!$stmt_inner->execute ()) {
                            $job_result = 'Error';
                            $job_msg = '155 ' . $db_link->error;
                            $success = false;
                            break;
                        }
                    }
                    $stmt_inner->close ();
                }
                else if ($success) {
                    $job_result = 'Error';
                    $job_msg = '164 ' . $db_link->error;
                    $success = false;
                    break;
                }
            }
        }
        else if ($success) {
            $job_result = 'Error';
            $job_msg = '172 ' . $system_id . ' ' . $db_link->error;
            $success = false;
            break;
        }

        // Sleep for five seconds before we request the next set of data
        // (try not to hit the enphase server too quickly)
        sleep (5);
    }
}

// Mark successful
if ($success) {
    $job_result = 'Success';
}
?>