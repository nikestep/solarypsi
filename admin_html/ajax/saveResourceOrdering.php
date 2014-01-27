<?php
/**
 * 
 *
 * @author Nik Estep
 * @date March 5, 2013
 */

// Include the configuration file
include ('/home/solaryps/config/config.php');

// Open connection to MySQL database and disable auto-commit
$db_link = new mysqli ($DB_SERVER, $DB_USERNAME, $DB_PASSWORD, $DB_DATABASE, $DB_PORT, $DB_SOCKET);
$db_link->autocommit (FALSE);

// Save the new resource order
$stmt = $db_link->prepare ("UPDATE " .
						   "    site_resource " .
						   "SET " .
						   "    disp_order=? " .
						   "WHERE " .
						   "    id=?");
$stmt->bind_param ('ii', $order, $db_id);
$order = 1;
$success = TRUE;
$err_msg = '';
foreach ($_POST['orderings'] as $key => $db_id) {
	if (!$stmt->execute ()) {
		$success = FALSE;
		$err_msg = $db_link->error;
		break;
	}
	$order += 1;
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

header ('Content-Type: application/json');
header ('Cache-Control: no-cache, must-revalidate');
header ('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
echo json_encode (array ('success' => $success, 'err_msg' => $err_msg));
?>