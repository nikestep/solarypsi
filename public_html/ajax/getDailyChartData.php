<?php
// Include the configuration file and shared methods file
include ('/home/solaryps/config/config.php');

// Declare a function to return the tick display value for a date
function getTickVal ($indx) {
    if ($indx === 36 || $indx === 180) {
        return '3:00';
    }
    else if ($indx === 72 || $indx === 216) {
        return '6:00';
    }
    else if ($indx === 108 || $indx === 252) {
        return '9:00';
    }
    else if ($indx === 144) {
        return '12:00';
    }
    else {
        return '';
    }
}

// Create the object to hold the response
$data = array ('success' => TRUE);

if (isset ($_GET['siteID'])) {
    // Set the date
    $date = date('Y-m-d');
    if (isset ($_GET['date'])) {
        $date = $_GET['date'];
    }

    // Connect to the database
    $db_link = new mysqli ($DB_SERVER, $DB_USERNAME, $DB_PASSWORD, $DB_DATABASE, $DB_PORT, $DB_SOCKET);

    // Retrieve the data
    $stmt = $db_link->prepare ("SELECT " .
                               "    point_index, " .
                               "    inflow, " .
                               "    outflow, " .
                               "    generation " .
                               "FROM " .
                               "    data_daily " .
                               "WHERE " .
                               "    site_id = ? " .
                               "  AND " .
                               "    point_date = ? " .
                               "ORDER BY " .
                               "    point_index ASC");
    $stmt->bind_param ('ss', $_GET['siteID'], $date);
    $stmt->execute ();
    $stmt->bind_result ($point_index, $inflow, $outflow, $generation);

    $idx = 0;
    $last_point_index = 0;
    $raw_data = Array ('inflow' => Array (), 'outflow' => Array (), 'generation' => Array ());
    while ($stmt->fetch ()) {
        // Store the point
        $raw_data['inflow'][$idx] = array ('date' => $point_index,
                                           'value' => $inflow);
        $raw_data['outflow'][$idx] = array ('date' => $point_index,
                                            'value' => $outflow);
        $raw_data['generation'][$idx] = array ('date' => $point_index,
                                               'value' => $generation);
        $idx += 1;
        $last_point_index = $point_index;
    }

    // Pad end (will only execute if necessary)
    while ($idx < 288) {
        // Missing data needs to be padded in
        $last_point_index += 5;
        $raw_data['inflow'][$idx] = array ('date' => $last_point_index,
                                           'value' => NULL);
        $raw_data['outflow'][$idx] = array ('date' => $last_point_index,
                                            'value' => NULL);
        $raw_data['generation'][$idx] = array ('date' => $last_point_index,
                                               'value' => NULL);
        $idx += 1;
    }
    
    // Format the data for return
    $data['data'] = array ();
    $data['x_ticks'] = array ();
    $temp = array ();
    foreach ($raw_data['inflow'] as $idx => $obj) {
        // Push the tick label
        $tick = array ();
        array_push ($tick, $idx);
        array_push ($tick, getTickVal ($idx));
        array_push ($data['x_ticks'], $tick);
        
        // Push the data point
        $point = array ();
        array_push ($point, $idx);
        array_push ($point, $obj['value']);
        array_push ($temp, $point);
    }
    $data['data']['inflow'] = array ('label' => 'Inflow Meter',
                                     'color' => '#' . $INFLOW_COLOR,
                                     'data' => $temp);
    
    $temp = array ();
    foreach ($raw_data['outflow'] as $idx => $obj) {
        // Push the data point
        $point = array ();
        array_push ($point, $idx);
        array_push ($point, $obj['value']);
        array_push ($temp, $point);
    }
    $data['data']['outflow'] =  array ('label' => 'Outflow Meter',
                                       'color' => '#' . $OUTFLOW_COLOR,
                                       'data' => $temp);
    
    $temp = array ();
    foreach ($raw_data['generation'] as $idx => $obj) {
        // Push the data point
        $point = array ();
        array_push ($point, $idx);
        array_push ($point, $obj['value']);
        array_push ($temp, $point);
    }
    $data['data']['generation'] =  array ('label' => 'Solar Panel Output',
                                          'color' => '#' . $GENERATION_COLOR,
                                          'data' => $temp);
}
else {
    // No site ID and metering type was provided so we cannot load data
    $data['success'] = FALSE;
}

// Encode the reponse
header ('Content-Type: application/json');
header ('Cache-Control: no-cache, must-revalidate');
header ('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
echo json_encode ($data);
?>