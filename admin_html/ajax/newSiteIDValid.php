<?php
/**
 * Check a potential new site ID.
 *
 * @author Nik Estep
 * @date March 1, 2013
 */

// Include the configuration file
include ('/home/solaryps/config/config.php');

// Open connection to MySQL database
$db_link = new mysqli ($DB_SERVER, $DB_USERNAME, $DB_PASSWORD, $DB_DATABASE, $DB_PORT, $DB_SOCKET);

// Query for the existance of the proposed site ID
$stmt = $db_link->prepare ("SELECT " .
		  				   "    id " .
						   "FROM " .
						   "    site " .
						   "WHERE " .
						   "    id=?");
$stmt->bind_param ('s', $_POST['siteID']);
$stmt->execute ();

$valid = TRUE;
if ($stmt->num_rows () != 0) {
	$valid = FALSE;
}

$stmt->close ();

// Close the database connection
$db_link->close ();

// Encode the reponse
header ('Content-Type: application/json');
header ('Cache-Control: no-cache, must-revalidate');
header ('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
echo json_encode (array ('isValid' => $valid,
						 'requestIndex' => intval ($_POST['requestIndex'])));					 
?>