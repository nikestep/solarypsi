<?php
// Track success of portions
$success = true;

// Set today
$local_tz = new DateTimeZone('America/New_York');
$curr_interval = new DateTime (null, $local_tz);
$date_str = $curr_interval->format ('Y-m-d');

// Get systems to load data for
$sites = Array ();
if ($stmt_inner = $db_link->prepare ("SELECT " .
                                     "    site_id, " .
                                     "    loc_lat, " .
                                     "    loc_long " .
                                     "FROM " .
                                     "    site_info " .
                                     "WHERE " .
                                     "    meter_type != 'none' " .
                                     "  AND " .
                                     "    meter_type != 'historical'")) {
    if (!$stmt_inner->execute ()) {
        $job_result = 'Error';
        $job_msg = '24 ' . $db_link->error;
        $success = false;
    }
    else {
        $stmt_inner->bind_result ($site_id, $latitude, $longitude);

        while ($stmt_inner->fetch ()) {
            $sites[$site_id] = Array ('latitude' => $latitude, 'longitude' => $longitude);
        }
    }
    $stmt_inner->close ();
}
else {
    $job_result = 'Error';
    $job_msg = '38 ' . $db_link->error;
    $success = false;
}

if ($success) {
    foreach ($sites as $site_id => $position) {
        // Retrieve the data from the internets
        $url = 'https://api.forecast.io/forecast/' . $FORECAST_IO_API_KEY . '/' .
               $position['latitude'] . ',' . $position['longitude'];
        $str = file_get_contents ($url);

        // Parse JSON
        $json = json_decode ($str, TRUE);

        // Get sunrise/sunset/noon times
        $temp_date = new DateTime();
        $temp_date->setTimestamp ($json['daily']['data'][0]['sunriseTime']);
        $sunrise_hour = intVal ($temp_date->format ('H'));
        $sunrise_mins = intVal ($temp_date->format ('i'));
        $sunrise_time = (60 * $sunrise_hour) + $sunrise_mins;
        $temp_date->setTimestamp ($json['daily']['data'][0]['sunsetTime']);
        $sunset_hour = intVal ($temp_date->format ('H'));
        $sunset_mins = intVal ($temp_date->format ('i'));
        $sunset_time = (60 * $sunset_hour) + $sunset_mins;
        $noon_time = ($sunrise_time + $sunset_time) / 2;
        $noon_hour = intVal ($noon_time / 60);
        $noon_mins = intVal ($noon_time % 60);

        // Calculate temperature times
        $temp_date->setTimestamp ($json['daily']['data'][0]['temperatureMinTime']);
        $temp_min_time = $temp_date->format ('g:i a');
        $temp_date->setTimestamp ($json['daily']['data'][0]['temperatureMaxTime']);
        $temp_max_time = $temp_date->format ('g:i a');
        $temp_date->setTimestamp ($json['daily']['data'][0]['apparentTemperatureMinTime']);
        $app_temp_min_time = $temp_date->format ('g:i a');
        $temp_date->setTimestamp ($json['daily']['data'][0]['apparentTemperatureMaxTime']);
        $app_temp_max_time = $temp_date->format ('g:i a');

        // Save the data
        if ($stmt_inner = $db_link->prepare ("INSERT INTO " .
                                             "    weather_data " .
                                             "VALUES " .
                                             "    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) " .
                                             "ON DUPLICATE KEY UPDATE " .
                                             "    sunrise_hour = ?, " .
                                             "    sunrise_minute = ?, " .
                                             "    noon_hour = ?, " .
                                             "    noon_minute = ?, " .
                                             "    sunset_hour = ?, " .
                                             "    sunset_minute = ?, " .
                                             "    description = ?, " .
                                             "    icon = ?, " .
                                             "    temperature_min = ?, " .
                                             "    temperature_min_time = ?, " .
                                             "    temperature_max = ?, " .
                                             "    temperature_max_time = ?, " .
                                             "    apparent_temperature_min = ?, " .
                                             "    apparent_temperature_min_time = ?, " .
                                             "    apparent_temperature_max = ?, " .
                                             "    apparent_temperature_max_time = ?")) {
            $stmt_inner->bind_param ('ssiiiiiissisisisisiiiiiissisisisis', $site_id,
                                                                           $date_str,
                                                                           $sunrise_hour,
                                                                           $sunrise_mins,
                                                                           $noon_hour,
                                                                           $noon_mins,
                                                                           $sunset_hour,
                                                                           $sunset_mins,
                                                                           $json['daily']['data'][0]['summary'],
                                                                           $json['daily']['data'][0]['icon'],
                                                                           $json['daily']['data'][0]['temperatureMin'],
                                                                           $temp_min_time,
                                                                           $json['daily']['data'][0]['temperatureMax'],
                                                                           $temp_max_time,
                                                                           $json['daily']['data'][0]['apparentTemperatureMin'],
                                                                           $app_temp_min_time,
                                                                           $json['daily']['data'][0]['apparentTemperatureMax'],
                                                                           $app_temp_max_time,
                                                                           $sunrise_hour,
                                                                           $sunrise_mins,
                                                                           $noon_hour,
                                                                           $noon_mins,
                                                                           $sunset_hour,
                                                                           $sunset_mins,
                                                                           $json['daily']['data'][0]['summary'],
                                                                           $json['daily']['data'][0]['icon'],
                                                                           $json['daily']['data'][0]['temperatureMin'],
                                                                           $temp_min_time,
                                                                           $json['daily']['data'][0]['temperatureMax'],
                                                                           $temp_max_time,
                                                                           $json['daily']['data'][0]['apparentTemperatureMin'],
                                                                           $app_temp_min_time,
                                                                           $json['daily']['data'][0]['apparentTemperatureMax'],
                                                                           $app_temp_max_time);
            if (!$stmt_inner->execute ()) {
                $job_result = 'Error';
                $job_msg = '134 ' . $db_link->error;
                $success = false;
            }
            $stmt_inner->close ();
        }
        else {
            $job_result = 'Error';
            $job_msg = '141 ' . $db_link->error;
            $success = false;
        }

        // Sleep for five seconds before we request the next set of data
        // (try not to hit the forecast.io server too quickly)
        sleep (5);
    }
}

// Mark successful
if ($success) {
    $job_result = 'Success';
}
?>