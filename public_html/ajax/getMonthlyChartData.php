<?php
// Include the configuration file and shared methods
include ('/home/solaryps/config/config.php');

// Declare a function to return the tick display value for a month
function getTickVal ($month) {
    $month_str = '';
    switch ($month) {
        case 1:
            $month_str = 'Jan';
            break;
        case 2:
            $month_str = 'Feb';
            break;
        case 3:
            $month_str = 'Mar';
            break;
        case 4:
            $month_str = 'Apr';
            break;
        case 5:
            $month_str = 'May';
            break;
        case 6:
            $month_str = 'Jun';
            break;
        case 7:
            $month_str = 'Jul';
            break;
        case 8:
            $month_str = 'Aug';
            break;
        case 9:
            $month_str = 'Sep';
            break;
        case 10:
            $month_str = 'Oct';
            break;
        case 11:
            $month_str = 'Nov';
            break;
        case 12:
            $month_str = 'Dec';
            break;
        default:
            break;
    }
    return $month_str;
}

// Open connection to MySQL database
$db_link = new mysqli ($DB_SERVER, $DB_USERNAME, $DB_PASSWORD, $DB_DATABASE, $DB_PORT, $DB_SOCKET);

// Create the object to hold the response
$data = array ('success' => TRUE);

if (isset ($_GET['siteID'])) {
    // Get the year to retrieve
    $year = intval (date('Y'));
    if (isset ($_GET['year'])) {
        $year = intval ($_GET['year']);
    }
    
    // Retrieve the data
    $stmt = $db_link->prepare ("SELECT " .
                               "    month, " .
                               "    inflow, " .
                               "    outflow, " .
                               "    generation " .
                               "FROM " .
                               "    data_monthly " .
                               "WHERE " .
                               "    site_id = ? " .
                               "  AND " .
                               "    year = ? " .
                               "ORDER BY " .
                               "    month ASC");
    
    $stmt->bind_param ('si', $_GET['siteID'], $year);
    $stmt->execute ();
    $stmt->bind_result ($month, $inflow, $outflow, $generation);
    
    $idx = 1;
    $raw_data = Array ('inflow' => Array (), 'outflow' => Array (), 'generation' => Array ());
    while ($stmt->fetch ()) {
        while ($idx < $month) {
            $raw_data['inflow'][$idx] = array ('year' => $year,
                                               'month' => $idx,
                                               'value' => null);
            $raw_data['outflow'][$idx] = array ('year' => $year,
                                                'month' => $idx,
                                                'value' => null);
            $raw_data['generation'][$idx] = array ('year' => $year,
                                                   'month' => $idx,
                                                   'value' => null);
            $idx += 1;
        }
        
        $raw_data['inflow'][$idx] = array ('year' => $year,
                                           'month' => $month,
                                           'value' => $inflow);
        $raw_data['outflow'][$idx] = array ('year' => $year,
                                            'month' => $month,
                                            'value' => $outflow);
        $raw_data['generation'][$idx] = array ('year' => $year,
                                               'month' => $month,
                                               'value' => $generation);
        $idx += 1;
    }

    $stmt->close ();
    
    // Pad end (will only execute if necessary)
    while ($idx <= 12) {
        // Missing data needs to be padded in
        $raw_data['inflow'][$idx] = array ('year' => $year,
                                           'month' => $idx,
                                           'value' => null);
        $raw_data['outflow'][$idx] = array ('year' => $year,
                                            'month' => $idx,
                                            'value' => null);
        $raw_data['generation'][$idx] = array ('year' => $year,
                                               'month' => $idx,
                                               'value' => null);
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
        array_push ($tick, getTickVal ($obj['month']));
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
    // No site ID was provided so we cannot load data
    $data['success'] = FALSE;
}

// Close the database connection
$db_link->close ();

// Encode the reponse
header ('Content-Type: application/json');
header ('Cache-Control: no-cache, must-revalidate');
header ('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
echo json_encode ($data);
?>