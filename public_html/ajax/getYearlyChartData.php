<?php
// Include the configuration file and shared methods file
include ('/home/solaryps/config/config.php');

// Declare a function to return the tick display value for a date
function getTickVal ($date) {
    if (substr ($date, -2) === '01') {
        return '|';
    }
    else if (substr ($date, -2) === '15') {
        return getMonthName (intval (substr ($date, 5, 2)), TRUE);
    }
    else {
        return '';
    }
}

// Open connection to MySQL database
$db_link = new mysqli ($DB_SERVER, $DB_USERNAME, $DB_PASSWORD, $DB_DATABASE, $DB_PORT, $DB_SOCKET);

// Create the object to hold the response
$data = array ('success' => TRUE);

if (isset ($_POST['siteID'])) {
    // Figure out what 'page' of points to retrieve
    $chart_idx = 0;
    $where_start = '';
    $where_end = '';
    if (isset ($_POST['chartIdx'])) {
        $chart_idx = intval ($_POST['chartIdx']);
    }
    
    $where_start = date ('Y-m-d', strtotime ('-' . (($chart_idx + 1) * $YEARLY_CHART_POINT_SIZE) . ' days'));
    $where_end = date ('Y-m-d', strtotime ('-' . ($chart_idx * $YEARLY_CHART_POINT_SIZE) . ' days'));
    
    // Retrieve the data
    $raw_data = array ('inflow' => array (),
                       'free_used' => array (),
                       'free_return' => array ());
    $stmt = $db_link->prepare ("SELECT " .
                               "    point_date, " .
                               "    inflow, " .
                               "    outflow, " .
                               "    generation " .
                               "FROM " .
                               "    data_yearly " .
                               "WHERE " .
                               "    site_id=? " .
                               "  AND " .
                               "    point_date>=? " .
                               "  AND " .
                               "    point_date<=? " .
                               "ORDER BY " .
                               "    point_date DESC");
    $stmt->bind_param ('sss', $_POST['siteID'],
                              $where_start,
                              $where_end);
    $stmt->execute ();
    $stmt->bind_result ($point_date, $inflow, $outflow, $generation);
    
    $idx = $YEARLY_CHART_POINT_SIZE;
    $last_point_date = NULL;
    while ($stmt->fetch () && $idx > 0) {
        if ($idx === $YEARLY_CHART_POINT_SIZE && !isYesterday ($point_date, $chart_idx)) {
            // Missing data needs to be padded in
            $curr_date = strtotime ('-1 day');
            if ($chart_idx !== 0) {
                for ($i = 0; $i < $chart_idx; $i++) {
                    $curr_date = strtotime ('-365 days', $curr_date);
                }
            }
            $curr_date_str = date ('Y-m-d', $curr_date);
            while ($idx > 0 && !datesEqual ($curr_date_str, $point_date)) {
                $raw_data['inflow'][$idx] = array ('date' => $curr_date_str,
                                                   'value' => NULL);
                $raw_data['outflow'][$idx] = array ('date' => $curr_date_str,
                                                    'value' => NULL);
                $raw_data['generation'][$idx] = array ('date' => $curr_date_str,
                                                       'value' => NULL);
                $idx -= 1;
                
                $curr_date = strtotime ('-1 day', $curr_date);
                $curr_date_str = date ('Y-m-d', $curr_date);
            }
        }
        
        /*Commented out as I am currently assuming the gaps will only be at the start of the data
        while ($last_point_date != NULL && datesEqual ($last_point_date, date ('Y-m-d', strtotime ('+1 day', strtotime ($point_date))))) {
            // Another gap
            $last_point_date = date ('Y-m-d', strtotime ('-1 day', strtotime ($last_point_date)));
            $raw_data['inflow'][$idx] = array ('date' => $last_point_date,
                                               'value' => NULL);
            $raw_data['outflow'][$idx] = array ('date' => $last_point_date,
                                                'value' => NULL);
            $raw_data['generation'][$idx] = array ('date' => $last_point_date,
                                                   'value' => NULL);
            $idx -= 1;
        }*/
        
        $raw_data['inflow'][$idx] = array ('date' => $point_date,
                                           'value' => $inflow);
        $raw_data['outflow'][$idx] = array ('date' => $point_date,
                                            'value' => $outflow);
        $raw_data['generation'][$idx] = array ('date' => $point_date,
                                               'value' => $generation);
        $idx -= 1;
        $last_point_date = $point_date;
    }

    $stmt->close ();
    
    // Pad end (will only execute if necessary)
    while ($idx > 0) {
        // Missing data needs to be padded in
        $curr_date = strtotime ('-1 day', strtotime ($last_point_date));
        $curr_date_str = date ('Y-m-d', $curr_date);
        $raw_data['inflow'][$idx] = array ('date' => $curr_date_str,
                                           'value' => NULL);
        $raw_data['outflow'][$idx] = array ('date' => $curr_date_str,
                                            'value' => NULL);
        $raw_data['generation'][$idx] = array ('date' => $curr_date_str,
                                               'value' => NULL);
        $idx -= 1;
        $last_point_date = $curr_date_str;
    }
    
    // Format the data for return
    $last_date_str = date ('F j, Y', strtotime ('-' . (1 + ($chart_idx * $YEARLY_CHART_POINT_SIZE)) . ' days'));
    $first_date_str = date ('F j, Y', strtotime ('-' . (($chart_idx + 1) * $YEARLY_CHART_POINT_SIZE) . ' days'));
    $data['title'] = "$first_date_str to $last_date_str";
    $markings = array ();
    array_push ($markings, array ('xaxis' => array ('from' => 0,
                                                    'to' => 0),
                                  'color' => '#000000'));
    $data['options'] = array ('series' => array ('lines' => array ('show' => TRUE,
                                                                    'steps' => FALSE),
                                                 'bars' => array ('show' => FALSE),
                                                 'hoverable' => TRUE),
                              'xaxis' => array ('ticks' => array (),
                                                  'tickLength' => 0),
                              'legend' => array ('show' => TRUE,
                                                   'noColumns' => 3),
                              'grid' => array ('borderWidth' => 2,
                                                 'aboveData' => TRUE,
                                                 'markings' => $markings));
    $data['data'] = array ();
    $temp = array ();
    foreach ($raw_data['inflow'] as $idx => $obj) {
        // Push the tick label
        $tick = array ();
        array_push ($tick, $idx);
        array_push ($tick, getTickVal ($obj['date']));
        array_push ($data['options']['xaxis']['ticks'], $tick);
        
        // Push the data point
        $point = array ();
        array_push ($point, $idx);
        array_push ($point, $obj['value']);
        array_push ($temp, $point);
    }
    array_push ($data['data'], array ('label' => 'Inflow Meter',
                                      'color' => '#' . $INFLOW_COLOR,
                                      'data' => $temp));
    
    $temp = array ();
    foreach ($raw_data['outflow'] as $idx => $obj) {
        // Push the data point
        $point = array ();
        array_push ($point, $idx);
        array_push ($point, $obj['value']);
        array_push ($temp, $point);
    }
    array_push ($data['data'], array ('label' => 'Outflow Meter',
                                      'color' => '#' . $OUTFLOW_COLOR,
                                      'data' => $temp));
    
    $temp = array ();
    foreach ($raw_data['generation'] as $idx => $obj) {
        // Push the data point
        $point = array ();
        array_push ($point, $idx);
        array_push ($point, $obj['value']);
        array_push ($temp, $point);
    }
    array_push ($data['data'], array ('label' => 'Generation Meter',
                                      'color' => '#' . $GENERATION_COLOR,
                                      'data' => $temp));
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