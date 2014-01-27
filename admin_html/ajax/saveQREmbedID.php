<?php
/**
 * Save the information for a site's QR video embed ID.
 *
 * @author Nik Estep
 * @date October 1, 2013
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
						   "    qr_code=? " .
						   "WHERE " .
						   "    site_id=?");

$stmt->bind_param ('ss', $_POST['qr_code'],
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

// Close the database connection
$db_link->close ();

// Encode the reponse
header ('Content-Type: application/json');
header ('Cache-Control: no-cache, must-revalidate');
header ('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
echo json_encode (array ('result' => $success,
						 'error_mysql' => $err_msg));
?>