<?php
/**
 * Create a new site.
 *
 * @author Nik Estep
 * @date March 3, 2013
 */

// Include the configuration file
include ('/home/solaryps/config/config.php');

// Open connection to MySQL database and disable auto-commit
$db_link = new mysqli ($DB_SERVER, $DB_USERNAME, $DB_PASSWORD, $DB_DATABASE, $DB_PORT, $DB_SOCKET);
$db_link->autocommit (FALSE);

// Create the site record
$stmt = $db_link->prepare ("INSERT INTO " .
						   "    site " .
						   "( " .
						   "    id, " .
						   "    description, " .
						   "    last_contact, " .
						   "    absence_reported " .
						   ") " .
						   "VALUES " .
						   "( " .
		  				   "    ?, " .
		  				   "    ?, " .
		  				   "    NULL, " .
		  				   "    0 " .
		  				   ")");
$stmt->bind_param ('ss', $_POST['siteID'], $_POST['description']);

$success = TRUE;
$err_msg = '';
if (!$stmt->execute ()) {
	$success = FALSE;
	$err_msg = $db_link->error;
}

$stmt->close ();

// Populate the defaults for the site info record
if ($success) {
	$stmt = $db_link->prepare ("INSERT INTO " .
							   "    site_info " .
							   "( " .
							   "    site_id " .
							   ") " .
							   "VALUES " .
							   "( " .
							   "    ? " .
							   ")");
	$stmt->bind_param ('s', $_POST['siteID']);

	if (!$stmt->execute ()) {
		$success = FALSE;
		$err_msg = $db_link->error;
	}
	
	$stmt->close ();
}

// Commit or rollback the transaction
if ($success) {
	$db_link->commit ();
}
else {
	$db_link->rollback ();
}

// If we succeeded, update static JSON files
if ($success) {
	// Start with map points
	include ('sub_tasks/update_map_points.php');
	
	// Update the pie chart data
	include ('sub_tasks/update_pie_data.php');
}

// If we succeeded in updating the database, retrieve a list of all sites for
// the dropdown list
$sites = array ();
if ($success) {
	$stmt = $db_link->prepare ("SELECT " .
							   "    id, " .
							   "    description " .
							   "FROM " .
							   "    site " .
							   "ORDER BY " .
							   "    description");
	$stmt->execute ();
	$stmt->bind_result ($id, $desc);
	
	while ($stmt->fetch ()) {
		$sites[$id] = $desc;
	}
	
	$stmt->close ();
}

// Close the database connection
$db_link->close ();

// Encode the reponse
header ('Content-Type: application/json');
header ('Cache-Control: no-cache, must-revalidate');
header ('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
echo json_encode (array ('result' => $success,
						 'error_msg' => $err_msg,
						 'siteID' => $_POST['siteID'],
						 'sites' => $sites));
?>