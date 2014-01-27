<?php
/**
 * Get monthly chart data for a site.
 *
 * @author Nik Estep
 * @date March 17, 2013
 */

// Include the configuration file and shared methods
include ('/home/solaryps/config/config.php');
include ('../data/shared_methods.php');

// Open connection to MySQL database
$db_link = new mysqli ($DB_SERVER, $DB_USERNAME, $DB_PASSWORD, $DB_DATABASE, $DB_PORT, $DB_SOCKET);

// Create the object to hold the response
$data = array ('success' => TRUE);

if (isset ($_POST['siteID'])) {
	// Figure out what 'page' of points to retrieve
	$chart_idx = 0;
	if (isset ($_POST['chartIdx'])) {
		$chart_idx = intval ($_POST['chartIdx']); 
	}
	$limit_start = $MONTHLY_CHART_POINT_SIZE * $chart_idx;
	
	// Retrieve the data
	$raw_data = array ('inflow' => array (),
					   'free_used' => array (),
					   'free_return' => array ());
	$stmt = $db_link->prepare ("SELECT " .
							   "    year, " .
							   "    month, " .
							   "    inflow, " .
							   "    outflow, " .
							   "    generation " .
							   "FROM " .
							   "    data_monthly " .
							   "WHERE " .
							   "    site_id=? " .
							   "ORDER BY " .
							   "    year DESC, " .
							   "    month DESC " .
							   "LIMIT ?, ?");
	$stmt->bind_param ('sii', $_POST['siteID'],
							  $limit_start,
							  $MONTHLY_CHART_POINT_SIZE);
	$stmt->execute ();
	$stmt->bind_result ($year, $month, $inflow, $outflow, $generation);
	
	$idx = $MONTHLY_CHART_POINT_SIZE;
	$last_year = 0;
	$last_month = 0;
	while ($idx > 0 && $stmt->fetch ()) {
		$gen_used = ($generation !== NULL && $outflow !== NULL) ? $generation - $outflow : NULL;
		
		$raw_data['inflow'][$idx] = array ('year' => $year,
										   'month' => $month,
										   'value' => $inflow);
		$raw_data['free_used'][$idx] = array ('year' => $year,
										      'month' => $month,
										      'value' => $gen_used);
		$raw_data['free_return'][$idx] = array ('year' => $year,
										        'month' => $month,
										        'value' => $outflow);
		$idx -= 1;
		$last_year = $year;
		$last_month = $month;
	}

	$stmt->close ();
	
	// Pad end (will only execute if necessary)
	while ($idx > 0) {
		// Missing data needs to be padded in
		$last_month -= 1;
		if ($last_month === 0) {
			$last_month = 12;
			$lastYear -= 1;
		}
		$raw_data['inflow'][$idx] = array ('year' => $last_year,
										   'month' => $last_month,
										   'value' => NULL);
		$raw_data['free_used'][$idx] = array ('year' => $last_year,
										      'month' => $last_month,
											  'value' => NULL);
		$raw_data['free_return'][$idx] = array ('year' => $last_year,
										        'month' => $last_month,
											    'value' => NULL);
		$idx -= 1;
	}
	
	// Format the data for return
	$last_month_str = date ('F Y', strtotime ('-' . (1 + ($chart_idx * $MONTHLY_CHART_POINT_SIZE)) . ' month'));
	$first_month_str = date ('F Y', strtotime ('-' . (($chart_idx + 1) * $MONTHLY_CHART_POINT_SIZE) . ' months'));
	$data['title'] = "Monthly Usage from $first_month_str to $last_month_str";
	$markings = array ();
	array_push ($markings, array ('xaxis' => array ('from' => 0,
													'to' => 0),
								  'color' => '#000000'));
	$data['options'] = array ('series' => array ('stack' => 0,
												 'lines' => array ('show' => FALSE,
												 				   'steps' => FALSE),
												 'bars' => array ('show' => TRUE,
												 				  'barWidth' => 0.9,
												 				  'align' => 'center')),
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
		array_push ($tick, getMonthName ($obj['month'], TRUE));
		array_push ($data['options']['xaxis']['ticks'], $tick);
		
		// Push the data point
		$point = array ();
		array_push ($point, $idx);
		array_push ($point, $obj['value']);
		array_push ($temp, $point);
	}
	array_push ($data['data'], array ('label' => 'Electricity Used - Purchased',
									  'color' => '#' . $INFLOW_COLOR,
									  'data' => $temp));
	
	$temp = array ();
	foreach ($raw_data['free_used'] as $idx => $obj) {
		// Push the data point
		$point = array ();
		array_push ($point, $idx);
		array_push ($point, $obj['value']);
		array_push ($temp, $point);
	}
	array_push ($data['data'], array ('label' => 'Electricity Used - Free',
									  'color' => '#' . $GENERATION_COLOR,
									  'data' => $temp));
	
	$temp = array ();
	foreach ($raw_data['free_return'] as $idx => $obj) {
		// Push the data point
		$point = array ();
		array_push ($point, $idx);
		array_push ($point, $obj['value']);
		array_push ($temp, $point);
	}
	array_push ($data['data'], array ('label' => 'Excess Electricity - Sold',
									  'color' => '#' . $OUTFLOW_COLOR,
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