<?php
// Include the configuration file and shared methods file
include ('/home/solaryps/config/config.php');
include ('./shared.php');

// Open connection to MySQL database
$db_link = new mysqli ($DB_SERVER, $DB_USERNAME, $DB_PASSWORD, $DB_DATABASE, $DB_PORT, $DB_SOCKET);

// Create the object to hold the response
$data = array ('success' => TRUE);

// Get the year to retrieve
$year = intval (date('Y'));
if (isset ($_GET['year'])) {
    $year = intval ($_GET['year']);
}

// Retrieve the data
$stmt = $db_link->prepare ("SELECT " .
			               "    data_monthly.site_id, " .
						   "    site.description, " .
                           "    data_monthly.year, " .
                           "    data_monthly.month, " .
                           "    data_monthly.generation " .
                           "FROM " .
                           "    data_monthly INNER JOIN site ON data_monthly.site_id = site.id " .
                           "WHERE " .
                           "    data_monthly.year = ? " .
                           "ORDER BY " .
						   "    data_monthly.site_id ASC, " .
                           "    data_monthly.month ASC");
$stmt->bind_param ('i', $year);
$stmt->execute ();
$stmt->bind_result ($site_id, $site_description, $year, $month, $generation);

$curr_site_id = '';
$raw_data = Array ();
$site_descriptions = Array ();

while ($stmt->fetch ()) {
    if ($curr_site_id !== $site_id) {
		$raw_data[$site_id] = Array (1 => null, 2 => null, 3 => null, 4 => null,
									 5 => null, 6 => null, 7 => null, 8 => null,
									 9 => null, 10 => null, 11 => null, 12 => null);
		$site_descriptions[$site_id] = $site_description;
		$curr_site_id = $site_id;
	}
    
    $raw_data[$site_id][$month] = $generation;
}
$stmt->close ();

// Format the data for return
$data['data'] = array ('bar' => array ());
$data['x_ticks'] = array ('bar' => array ());
foreach (range(1, 12) as $month) {
	$tick = array ();
    array_push ($tick, $month - 1);
    array_push ($tick, getBarTickVal ($month, $_GET['mobile'] === 'true'));
    array_push ($data['x_ticks']['bar'], $tick);
}
foreach ($raw_data as $site_id => $obj) {
	$temp = array ();
	foreach ($obj as $month => $val) {
	    array_push ($temp, array ($month - 1, $val));
	}
	array_push ($data['data']['bar'], array ('label' => $site_descriptions[$site_id],
	                                         'data' => $temp));
}

// Close the database connection
$db_link->close ();

// Encode the reponse
header ('Content-Type: application/json');
header ('Cache-Control: no-cache, must-revalidate');
header ('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
echo json_encode ($data);
?>