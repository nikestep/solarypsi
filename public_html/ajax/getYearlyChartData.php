<?php
// Include the configuration file and shared methods file
include ('/home/solaryps/config/config.php');

// Declare a function to return the tick display value for a date
function getTickVal ($date) {
    if (substr ($date, -2) === '01' && substr ($date, 5, 2) !== '01') {
        return '|';
    }
    else if (substr ($date, -2) === '15') {
        $month = intval (substr ($date, 5, 2));
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
    else {
        return '';
    }
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
                               "    point_date, " .
                               "    inflow, " .
                               "    outflow, " .
                               "    generation " .
                               "FROM " .
                               "    data_yearly " .
                               "WHERE " .
                               "    site_id = ? " .
                               "  AND " .
                               "    point_year = ? " .
                               "ORDER BY " .
                               "    point_date ASC");
    $stmt->bind_param ('si', $_GET['siteID'], $year);
    $stmt->execute ();
    $stmt->bind_result ($point_date, $inflow, $outflow, $generation);
    
    $last_point_date = DateTime::createFromFormat ('Y-m-d', $year . '-01-01');
    $last_point_date->setTime (1, 0, 0);
    $idx = 0;
    $raw_data = Array ('inflow' => Array (), 'outflow' => Array (), 'generation' => Array ());
    while ($stmt->fetch ()) {
        $pdate = DateTime::createFromFormat ('Y-m-d', $point_date);
        $pdate->setTime (0, 0, 0);
        while ($last_point_date < $pdate) {
            $lpd_str = $last_point_date->format ('Y-m-d');
            $raw_data['inflow'][$idx] = Array ('date' => $lpd_str,
                                               'value' => null);
            $raw_data['outflow'][$idx]= Array ('date' => $lpd_str,
                                               'value' => null);
            $raw_data['generation'][$idx]= Array ('date' => $lpd_str,
                                                  'value' => null);
            $last_point_date->add (date_interval_create_from_date_string ('1 day'));
            $idx += 1;
        }
        
        $pdate_str = $pdate->format ('Y-m-d');
        $raw_data['inflow'][$idx] = array ('date' => $pdate_str,
                                           'value' => $inflow);
        $raw_data['outflow'][$idx] = array ('date' => $pdate_str,
                                            'value' => $outflow);
        $raw_data['generation'][$idx] = array ('date' => $pdate_str,
                                               'value' => $generation);
        $last_point_date->add (date_interval_create_from_date_string ('1 day'));
        $idx += 1;
    }
    $stmt->close ();
    
    // Pad end (will only execute if necessary)
    $end_of_year = DateTime::createFromFormat ('Y-m-d', $year . '-12-31');
    $end_of_year->setTime (23, 59, 59);
    while ($last_point_date < $end_of_year) {
        // Missing data needs to be padded in
        $lpd_str = $last_point_date->format ('Y-m-d');
        $raw_data['inflow'][$idx] = Array ('date' => $lpd_str,
                                           'value' => null);
        $raw_data['outflow'][$idx]= Array ('date' => $lpd_str,
                                           'value' => null);
        $raw_data['generation'][$idx]= Array ('date' => $lpd_str,
                                              'value' => null);
        $last_point_date->add (date_interval_create_from_date_string ('1 day'));
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
        array_push ($tick, getTickVal ($obj['date']));
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
    $data['data']['generation'] =  array ('label' => 'Generation Meter',
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