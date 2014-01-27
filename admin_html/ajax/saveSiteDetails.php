<?php
/**
 * Save the information for a site from the 'Basic Information' tab.
 *
 * @author Nik Estep
 * @date March 7, 2013
 */

// Include the configuration file
include ('/home/solaryps/config/config.php');

// Open connection to MySQL database and disable auto-commit
$db_link = new mysqli ($DB_SERVER, $DB_USERNAME, $DB_PASSWORD, $DB_DATABASE, $DB_PORT, $DB_SOCKET);
$db_link->autocommit (FALSE);

// Create the site record
$stmt = $db_link->prepare ("UPDATE " .
						   "    site_info " .
						   "SET " .
						   "    inst_type=?, " .
						   "    completed=?, " .
						   "    panel_desc=?, " .
						   "    panel_angle=?, " .
						   "    inverter=?, " .
						   "    rated_output=?, " .
						   "    installer=?, " .
						   "    installer_url=?, " .
						   "    contact=?, " .
						   "    contact_url=?, " .
						   "    list_desc=?, " .
						   "    status=?, " .
						   "    loc_city=?, " .
						   "    loc_long=?, " .
						   "    loc_lat=?, " .
						   "    max_wh=?, " .
						   "    max_kw=?, " .
						   "    meter_type=? " .
						   "WHERE " .
						   "    site_id=?");

$stmt->bind_param ('sssssisssssssddidss', $_POST['inst_type'],
									      $_POST['completed'],
									      $_POST['panel_desc'],
									      $_POST['panel_angle'],
									      $_POST['inverter'],
									      $_POST['rated_output'],
									      $_POST['installer'],
									      $_POST['installer_url'],
									      $_POST['contact'],
									      $_POST['contact_url'],
									      $_POST['list_desc'],
									      $_POST['status'],
									      $_POST['loc_city'],
									      $_POST['loc_long'],
									      $_POST['loc_lat'],
									      $_POST['max_wh'],
									      $_POST['max_kw'],
									      $_POST['meter_type'],
									      $_POST['siteID']);

$success = TRUE;
$err_msg = '';
if (!$stmt->execute ()) {
	$success = FALSE;
	$err_msg = $db_link->error;
}

$stmt->close ();

// Commit or rollback the transaction
if ($success) {
	$db_link->commit ();
}
else {
	$db_link->rollback ();
}

// If the transaction was committed, update static JSON files
if ($success) {
	// Start with map points
	include ('sub_tasks/update_map_points.php');
	
	// Update the pie chart data
	include ('sub_tasks/update_pie_data.php');
}

// Close the database connection
$db_link->close ();

// Encode the reponse
header ('Content-Type: application/json');
header ('Cache-Control: no-cache, must-revalidate');
header ('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
echo json_encode (array ('result' => $success,
						 'error_mysql' => $err_msg));
?>