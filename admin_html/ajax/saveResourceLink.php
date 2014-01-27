<?php
/**
 * Save a link for a given site to the resource table.
 *
 * @author Nik Estep
 * @date March 30, 2013
 */

// Include the configuration file
include ('/home/solaryps/config/config.php');

// Open connection to MySQL database and disable auto-commit
$db_link = new mysqli ($DB_SERVER, $DB_USERNAME, $DB_PASSWORD, $DB_DATABASE, $DB_PORT, $DB_SOCKET);
$db_link->autocommit (FALSE);

$err_msg = '';
$db_id = 0;
$success = TRUE;

// Get the next display order index
$stmt = $db_link->prepare ("SELECT " .
						   "    MAX(disp_order) " .
						   "FROM " .
						   "    site_resource " .
						   "WHERE " .
						   "    site_id=? " .
						   "  AND " .
						   "    (res_type='document' OR res_type='link')");
$stmt->bind_param ('s', $_POST['siteID']);
$stmt->execute ();
$stmt->bind_result ($cnt);
$stmt->fetch ();

if ($cnt === NULL) {
	$cnt = 0;
}
$cnt += 1;
	
$stmt->close ();

// Create the resource record
$stmt = $db_link->prepare ("INSERT INTO " .
						   "    site_resource " .
						   "( " .
						   "    site_id, " .
						   "    res_type, " .
						   "    disp_order, " .
						   "    title, " .
						   "    res_desc, " .
						   "    file_path, " .
						   "    width, " .
						   "    height, " .
						   "    thumb_width, " .
						   "    thumb_height " .
						   ") " .
						   "VALUES " .
						   "( " .
						   "    ?, " .
						   "    'link', " .
						   "    ?, " .
						   "    ?, " .
						   "    ?, " .
						   "    ?, " .
						   "    0, " .
						   "    0, " .
						   "    0, " .
						   "    0 " .
						   ")");
if (!$stmt) {
	$success = FALSE;
	$err_msg = $db_link->error;
}

if ($success) {
	$index_path = $repos_pattern . $file_name;
	$stmt->bind_param ('sisss', $_POST['siteID'],
								$cnt,
								$_POST['title'],
								$_POST['description'],
								$_POST['link']);
	$stmt->execute ();

	if ($stmt->affected_rows === 0) {
		$success = FALSE;
		$err_msg = $db_link->error;
	}
	else {
		$db_id = $db_link->insert_id;
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

// Close the database connection
$db_link->close ();

// Send the response
header ('Content-Type: application/json');
header ('Cache-Control: no-cache, must-revalidate');
header ('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
echo json_encode (array ('success' => $success,
						 'id' => $db_id,
						 'err_mysql' => $err_msg,
						 'path' => $_POST['link'],
						 'title' => $_POST['title'],
						 'desc' => $_POST['description'],
						 'type' => $_POST['resourceType']));	
?>
